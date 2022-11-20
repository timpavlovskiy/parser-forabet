package helpers

import (
	"context"
	"sync"
	"time"
)

type ProxyChecker struct {
	mutex               sync.Mutex
	failedProxies       map[string]int
	redisClient         *RedisClient
	failedAttemptsCount int
}

func NewProxyChecker(redisClient *RedisClient) *ProxyChecker {
	return &ProxyChecker{
		failedProxies:       make(map[string]int),
		redisClient:         redisClient,
		failedAttemptsCount: 50,
	}
}

func (proxyChecker *ProxyChecker) SetErrorForProxy(proxy string) {
	proxyChecker.mutex.Lock()
	if counter, ok := proxyChecker.failedProxies[proxy]; ok {
		if false == proxyChecker.isFailedProxyByCounter(counter) {
			proxyChecker.failedProxies[proxy] = counter + 1
		}
	} else {
		proxyChecker.failedProxies[proxy] = 0
	}
	proxyChecker.mutex.Unlock()
}

func (proxyChecker *ProxyChecker) SetSuccessForProxy(proxy string) {
	proxyChecker.mutex.Lock()
	proxyChecker.failedProxies[proxy] = 0
	proxyChecker.mutex.Unlock()
}

func (proxyChecker *ProxyChecker) isFailedProxyByCounter(counter int) bool {
	return counter >= proxyChecker.failedAttemptsCount
}

func (proxyChecker *ProxyChecker) Clear() {
	proxyChecker.mutex.Lock()
	proxyChecker.failedProxies = make(map[string]int)
	proxyChecker.mutex.Unlock()
}

func (proxyChecker *ProxyChecker) Run(ctx context.Context, wait *sync.WaitGroup) {

loop:
	for {
		loopWaiting, _ := context.WithDeadline(context.Background(), time.Now().Add(10*time.Second))

		failedProxies := make([]string, 0, 5)
		proxyChecker.mutex.Lock()
		for proxy, counter := range proxyChecker.failedProxies {
			if proxyChecker.isFailedProxyByCounter(counter) {
				failedProxies = append(failedProxies, proxy)
			}
		}
		for _, proxy := range failedProxies {
			delete(proxyChecker.failedProxies, proxy)
		}
		proxyChecker.mutex.Unlock()
		if len(failedProxies) > 0 {
			proxyChecker.redisClient.SetFailedProxy(failedProxies)
		}
		<-loopWaiting.Done()
		select {
		case <-ctx.Done():
			break loop
		default:
		}
	}
	wait.Done()
}
