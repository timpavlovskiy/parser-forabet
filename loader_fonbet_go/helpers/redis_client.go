package helpers

import (
	"context"
	"encoding/json"
	"github.com/go-redis/redis"
	"log"
	"sync"
	"time"
)

type RedisClient struct {
	client                   *redis.Client
	eventKeyTTL              int
	prefixForEvent           string
	prefix                   string
	redisEventIdsChannel     string
	redisProxyChannel        string
	redisKeyForFailedProxies string
	subscribe                func(message redis.Message)
	logger                   *log.Logger
}

func NewRedisClient(
	client *redis.Client,
	eventKeyTTL int,
	prefixForEvent,
	prefix,
	redisEventIdsChannel,
	redisProxyChannel,
	redisKeyForFailedProxies string,
) *RedisClient {
	return &RedisClient{
		client:                   client,
		eventKeyTTL:              eventKeyTTL,
		prefixForEvent:           prefixForEvent,
		prefix:                   prefix,
		redisEventIdsChannel:     redisEventIdsChannel,
		redisProxyChannel:        redisProxyChannel,
		redisKeyForFailedProxies: redisKeyForFailedProxies,
	}
}

func (redisClient *RedisClient) SetLogger(logger *log.Logger) {
	redisClient.logger = logger
}

func (redisClient *RedisClient) SetFailedProxy(proxies []string) {

	if len(proxies) > 0 {
		if jsonProxies, err := json.Marshal(proxies); err == nil {
			key := redisClient.prefix + redisClient.redisKeyForFailedProxies
			redisClient.client.Set(key, jsonProxies, 12*time.Hour).Result()
		}
	}
}

func (redisClient *RedisClient) Get(key string) (string, error) {

	return redisClient.client.Get(redisClient.prefix + key).Result()
}

func (redisClient *RedisClient) SaveEvent(eventId string, body []byte) (string, error) {
	key := redisClient.prefix + redisClient.prefixForEvent + ":" + eventId
	ttl := time.Duration(redisClient.eventKeyTTL) * time.Second
	return redisClient.client.Set(key, body, ttl).Result()
}

func (redisClient *RedisClient) SubscribeEventIdsChannel(ctx context.Context, subscriber func(message *redis.Message)) {

	subscribeKey := redisClient.prefix + redisClient.redisEventIdsChannel
	pubsub := redisClient.client.WithContext(ctx).Subscribe(subscribeKey)
	_, err := pubsub.Receive()
	if err != nil {
		redisClient.logger.Fatalf("Не удалось создать подписку: %s", err)
	}
	ch := pubsub.Channel()
L:
	for {
		select {
		case msg := <-ch:
			subscriber(msg)
		case <-ctx.Done():
			break L
		}
	}
	pubsub.Close()
}

func (redisClient *RedisClient) SubscribeProxyChannel(ctx context.Context, subscriber func(message *redis.Message), wait *sync.WaitGroup) {

	if redisClient.redisProxyChannel == "" {
		wait.Done()
		return
	}
	subscribeKey := redisClient.prefix + redisClient.redisProxyChannel
	pubsub := redisClient.client.WithContext(ctx).Subscribe(subscribeKey)
	_, err := pubsub.Receive()
	if err != nil {
		redisClient.logger.Fatalf("Не удалось создать подписку: %s", err)
	}
	ch := pubsub.Channel()
L:
	for {
		select {
		case msg := <-ch:
			subscriber(msg)
		case <-ctx.Done():
			break L
		}
	}
	pubsub.Close()
	wait.Done()
}
