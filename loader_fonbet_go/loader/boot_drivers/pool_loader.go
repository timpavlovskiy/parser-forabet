package boot_drivers

import (
	"loader_fonbet_go/helpers"
	"loader_fonbet_go/loader"
	"log"
	"sync"
)

type PoolLoader struct {
	requestChannel  chan loader.RequestMessageDTO
	responseChannel chan loader.ResponseMessageDTO
	waitGroup       sync.WaitGroup
	ClientFactory   *loader.ClientHttpFactory
	PoolSize        int
	CountAttempts   int
	Logger          *log.Logger
	ProxyChecker    *helpers.ProxyChecker
}

func NewPoolLoader(clientFactory *loader.ClientHttpFactory, poolSize int, countAttempts int, checker *helpers.ProxyChecker) *PoolLoader {
	if countAttempts < 1 {
		countAttempts = 1
	}

	return &PoolLoader{
		ClientFactory:   clientFactory,
		PoolSize:        poolSize,
		CountAttempts:   countAttempts,
		requestChannel:  make(chan loader.RequestMessageDTO),
		responseChannel: make(chan loader.ResponseMessageDTO),
		Logger:          log.Default(),
		ProxyChecker:    checker,
	}
}

func (poolLoader *PoolLoader) GetRequestChannel() chan<- loader.RequestMessageDTO {
	return poolLoader.requestChannel
}

func (poolLoader *PoolLoader) GetResponseChannel() <-chan loader.ResponseMessageDTO {
	return poolLoader.responseChannel
}

func (poolLoader *PoolLoader) Start() {
	for i := 0; i < poolLoader.PoolSize; i++ {
		poolLoader.waitGroup.Add(1)
		go poolLoader.worker()
	}
}

func (poolLoader *PoolLoader) worker() {
	for requestMessage := range poolLoader.requestChannel {
		var bodyBytes []byte
		for step := 0; step < poolLoader.CountAttempts; step++ {
			httpClient, proxy := poolLoader.ClientFactory.GetClient()
			response, err := httpClient.Do(requestMessage.Body)
			if err != nil {
				poolLoader.Logger.Printf("HTTP request error %v", err)
				if proxy != "" {
					poolLoader.ProxyChecker.SetErrorForProxy(proxy)
				}
				continue
			}

			bodyBytes, err = helpers.ReadHttpResponse(response)
			_ = response.Body.Close()

			if proxy != "" {
				poolLoader.ProxyChecker.SetSuccessForProxy(proxy)
			}
			if err == nil {
				break
			}
		}
		poolLoader.responseChannel <- loader.ResponseMessageDTO{
			Label:     requestMessage.Label,
			Body:      bodyBytes,
			TimeStart: requestMessage.TimeStart,
		}
	}
	poolLoader.waitGroup.Done()
}

func (poolLoader *PoolLoader) StopAndWait() {
	close(poolLoader.requestChannel)
	poolLoader.waitGroup.Wait()
	close(poolLoader.responseChannel)
}
