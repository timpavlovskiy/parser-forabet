package commands

import (
	"context"
	"fmt"
	"github.com/go-redis/redis"
	"loader_fonbet_go/helpers"
	"loader_fonbet_go/loader"
	"loader_fonbet_go/loader/boot_drivers"
	"log"
	"os"
	"os/signal"
	"sync"
	"syscall"
)

type LoadEvents struct {
	loader            *loader.Loader
	redisClient       *helpers.RedisClient
	Logger            *log.Logger
	ctx               context.Context
	cancelCtx         context.CancelFunc
	clientHttpFactory *loader.ClientHttpFactory
	ProxyChecker      *helpers.ProxyChecker
}

func NewLoadEvents(
	httpHeaders string,
	httpTimeout int,
	countAttemptsLoad int,
	templateUrlEvent string,
	countHTTPWorkers int,
	isUseHttpProxy bool,
	proxyList string,
	redisHostEnvName string,
	redisPortEnvName string,
	redisSelectDb int,
	redisPrefix string,
	redisPrefixForEvent string,
	redisEventKeyTTL int,
	redisEventIdsChannel string,
	redisProxyChannel string,
	redisKeyForFailedProxies string,
	updateEventDuration int,
	maxConnections int,
) *LoadEvents {

	ctx := context.Background()
	ctx, cancel := context.WithCancel(ctx)

	logger := log.Default()
	//headers
	httpHeadersMap, err := helpers.JsonDecode[map[string]string](httpHeaders)
	if err != nil {
		logger.Fatalf("Некорректные заголовки %v", err)
	}

	//redis
	redisHost := os.Getenv(redisHostEnvName)
	redisPort := os.Getenv(redisPortEnvName)
	redisDbClient := redis.NewClient(&redis.Options{
		Addr:     fmt.Sprintf("%s:%s", redisHost, redisPort),
		Password: "",
		DB:       redisSelectDb,
	})
	redisDbClient = redisDbClient.WithContext(ctx)
	_, err = redisDbClient.Ping().Result()
	if err != nil {
		logger.Fatalf("Не удалось подключиться к редису %v", err)
	}
	redisClient := helpers.NewRedisClient(
		redisDbClient,
		redisEventKeyTTL,
		redisPrefixForEvent,
		redisPrefix,
		redisEventIdsChannel,
		redisProxyChannel,
		redisKeyForFailedProxies,
	)
	redisClient.SetLogger(logger)

	proxyChecker := helpers.NewProxyChecker(redisClient)

	//http client
	var proxySlice []string
	if isUseHttpProxy {
		if proxyList != "" {
			if proxySlice, err = helpers.JsonDecode[[]string](proxyList); err != nil {
				logger.Printf("Ошибка разбора проксей полученных из cli –proxyList %v", err)
			}
		}
	}

	clientHttpFactory := loader.NewClientHttpFactory(proxySlice, httpTimeout, maxConnections)
	clientHttpFactory.Logger = logger
	var bootDriver loader.BootDriver
	switch {
	case countHTTPWorkers > 0:
		bootDriver = boot_drivers.NewPoolLoader(clientHttpFactory, countHTTPWorkers, countAttemptsLoad, proxyChecker)
	default:
		bootDriver = boot_drivers.NewMultipleLoader(clientHttpFactory, countAttemptsLoad, proxyChecker)
	}

	newLoader := loader.NewLoader(
		bootDriver,
		httpHeadersMap,
		templateUrlEvent,
		updateEventDuration,
	)
	newLoader.Logger = logger

	return &LoadEvents{
		redisClient:       redisClient,
		loader:            newLoader,
		Logger:            logger,
		ctx:               ctx,
		cancelCtx:         cancel,
		clientHttpFactory: clientHttpFactory,
		ProxyChecker:      proxyChecker,
	}
}

func (loadEvents *LoadEvents) Run() error {

	waitLoadEvents := new(sync.WaitGroup)
	exitChannel := make(chan os.Signal, 1)
	signal.Notify(exitChannel, syscall.SIGINT, syscall.SIGTERM, syscall.SIGQUIT)
	waitLoadEvents.Add(1)
	go func() {
		<-exitChannel
		loadEvents.cancelCtx()
		waitLoadEvents.Done()
	}()

	//обрабатываем заблокированные прокси
	waitLoadEvents.Add(1)
	go loadEvents.ProxyChecker.Run(loadEvents.ctx, waitLoadEvents)

	//обрабатываем новые прокси
	waitLoadEvents.Add(1)
	go loadEvents.redisClient.SubscribeProxyChannel(loadEvents.ctx, func(message *redis.Message) {
		proxies, err := helpers.JsonDecode[[]string](message.Payload)
		if err != nil {
			loadEvents.Logger.Printf("Ошибка разбора новых прокси: %v %v", err, message.Payload)
			return
		}
		loadEvents.clientHttpFactory.SetProxies(proxies)
		loadEvents.ProxyChecker.Clear()
	}, waitLoadEvents)

	//загрузка событий
	waitLoadEvents.Add(1)
	go loadEvents.loader.LoadEvents(loadEvents.ctx, func(eventId string, body []byte) {
		if _, err := loadEvents.redisClient.SaveEvent(eventId, body); err != nil {
			loadEvents.Logger.Printf("Ошибка сохранения данных в редис: %v", err)
		}
	}, waitLoadEvents)

	//обрабатываем новые события
	loadEvents.redisClient.SubscribeEventIdsChannel(loadEvents.ctx, func(message *redis.Message) {
		dbEvents, err := helpers.JsonDecode[[]string](message.Payload)
		if err != nil {
			loadEvents.Logger.Printf("Ошибка разбора идентификаторов событий от редиса: %v %v", err, message.Payload)
			return
		}
		loadEvents.loader.SetDbEventIds(dbEvents)
	})
	waitLoadEvents.Wait()

	return nil
}
