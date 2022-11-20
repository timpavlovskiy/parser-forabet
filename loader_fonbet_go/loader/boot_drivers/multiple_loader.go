package boot_drivers

import (
	"loader_fonbet_go/helpers"
	"loader_fonbet_go/loader"
	"log"
	"sync"
)

type MultipleLoader struct {
	requestChannel  chan loader.RequestMessageDTO
	responseChannel chan loader.ResponseMessageDTO
	waitGroup       sync.WaitGroup
	ClientFactory   *loader.ClientHttpFactory
	CountAttempts   int
	Logger          *log.Logger
	ProxyChecker    *helpers.ProxyChecker
}

func NewMultipleLoader(clientFactory *loader.ClientHttpFactory, countAttempts int, checker *helpers.ProxyChecker) *MultipleLoader {
	if countAttempts < 1 {
		countAttempts = 1
	}

	return &MultipleLoader{
		ClientFactory:   clientFactory,
		CountAttempts:   countAttempts,
		requestChannel:  make(chan loader.RequestMessageDTO),
		responseChannel: make(chan loader.ResponseMessageDTO),
		Logger:          log.Default(),
		ProxyChecker:    checker,
	}
}

func (multipleLoader *MultipleLoader) GetRequestChannel() chan<- loader.RequestMessageDTO {
	return multipleLoader.requestChannel
}

func (multipleLoader *MultipleLoader) GetResponseChannel() <-chan loader.ResponseMessageDTO {
	return multipleLoader.responseChannel
}

func (multipleLoader *MultipleLoader) Start() {
	multipleLoader.waitGroup.Add(1)
	go multipleLoader.worker()
}

func (multipleLoader *MultipleLoader) worker() {
	for requestMessage := range multipleLoader.requestChannel {
		multipleLoader.waitGroup.Add(1)
		go func(requestMessage loader.RequestMessageDTO) {
			var bodyBytes []byte
			for step := 0; step < multipleLoader.CountAttempts; step++ {
				httpClient, proxy := multipleLoader.ClientFactory.GetClient()
				response, err := httpClient.Do(requestMessage.Body)
				if err != nil {
					if proxy != "" {
						multipleLoader.ProxyChecker.SetErrorForProxy(proxy)
					}
					//multipleLoader.Logger.Printf("HTTP request error %v", err)
					continue
				}
				bodyBytes, err = helpers.ReadHttpResponse(response)
				_ = response.Body.Close()
				if proxy != "" {
					multipleLoader.ProxyChecker.SetSuccessForProxy(proxy)
				}
				if err == nil {
					//multipleLoader.Logger.Printf("HTTP response error %v", err)
					break
				}
			}
			multipleLoader.responseChannel <- loader.ResponseMessageDTO{
				Label:     requestMessage.Label,
				Body:      bodyBytes,
				TimeStart: requestMessage.TimeStart,
			}
			multipleLoader.waitGroup.Done()
		}(requestMessage)
	}
	multipleLoader.waitGroup.Done()
}

func (multipleLoader *MultipleLoader) StopAndWait() {
	close(multipleLoader.requestChannel)
	multipleLoader.waitGroup.Wait()
	close(multipleLoader.responseChannel)
}
