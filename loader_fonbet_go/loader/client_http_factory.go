package loader

import (
	"errors"
	"log"
	"net/http"
	"net/url"
	"sync"
	"time"
)

type ClientHttpFactory struct {
	proxyList               []string
	httpTimeout             int
	Logger                  *log.Logger
	mutex                   sync.Mutex
	clientMap               map[string]*http.Client
	maxConnections          int
	proxyIndex              int
	proxyCount              int
	connectionCountInClient int
}

func NewClientHttpFactory(proxyList []string, httpTimeout int, maxConnections int) *ClientHttpFactory {

	if maxConnections == 0 {
		maxConnections = 1000
	}

	countProxy := len(proxyList)
	connectionCountInClient := maxConnections
	if countProxy > 0 {
		connectionCountInClient = int(maxConnections / countProxy)
	}

	return &ClientHttpFactory{
		proxyList,
		httpTimeout,
		log.Default(),
		sync.Mutex{},
		map[string]*http.Client{},
		maxConnections,
		0,
		countProxy,
		connectionCountInClient,
	}
}

func (clientHttpFactory *ClientHttpFactory) GetClient() (*http.Client, string) {

	proxy := ""
	clientKey := clientHttpFactory.getClientKey()
	client, err := clientHttpFactory.getClientFromCacheByKey(clientKey)

	if clientHttpFactory.isUsingProxy() {
		proxy = clientKey
	}
	if err == nil {
		clientHttpFactory.nextClient()
		return client, proxy
	}

	client, err = clientHttpFactory.createNewClientByClientKey(clientKey)
	if err != nil {
		clientHttpFactory.Logger.Printf("Ошибка создания клиента: %v", err)
	}
	clientHttpFactory.nextClient()

	clientHttpFactory.saveClientToCache(clientKey, client)
	return client, proxy
}

func (clientHttpFactory *ClientHttpFactory) SetProxies(proxies []string) {

	proxyCount := len(proxies)
	if proxyCount == 0 {
		return
	}

	clientHttpFactory.mutex.Lock()
	clientHttpFactory.proxyIndex = 0
	clientHttpFactory.proxyCount = proxyCount
	clientHttpFactory.connectionCountInClient = int(clientHttpFactory.maxConnections / proxyCount)
	clientHttpFactory.clientMap = map[string]*http.Client{}
	clientHttpFactory.proxyList = proxies
	clientHttpFactory.mutex.Unlock()
}

func (clientHttpFactory *ClientHttpFactory) isUsingProxy() bool {

	isUsingProxy := false

	clientHttpFactory.mutex.Lock()
	isUsingProxy = clientHttpFactory.proxyCount > 0
	clientHttpFactory.mutex.Unlock()

	return isUsingProxy
}

func (clientHttpFactory *ClientHttpFactory) getClientKey() string {
	clientKey := "default"
	if clientHttpFactory.isUsingProxy() {
		clientKey = clientHttpFactory.proxyList[clientHttpFactory.proxyIndex]
	}
	return clientKey
}

func (clientHttpFactory *ClientHttpFactory) getClientFromCacheByKey(clientKey string) (*http.Client, error) {
	clientHttpFactory.mutex.Lock()
	client, ok := clientHttpFactory.clientMap[clientKey]
	clientHttpFactory.mutex.Unlock()
	if ok {
		return client, nil
	}
	return client, errors.New("клиент не найден")
}

func (clientHttpFactory *ClientHttpFactory) nextClient() {
	if clientHttpFactory.isUsingProxy() {
		clientHttpFactory.mutex.Lock()
		if clientHttpFactory.proxyIndex == clientHttpFactory.proxyCount-1 {
			clientHttpFactory.proxyIndex = 0
		} else {
			clientHttpFactory.proxyIndex++
		}
		clientHttpFactory.mutex.Unlock()
	}
}

func (clientHttpFactory *ClientHttpFactory) createNewClientByClientKey(clientKey string) (*http.Client, error) {

	transport := &http.Transport{
		MaxIdleConns:        clientHttpFactory.connectionCountInClient,
		MaxConnsPerHost:     clientHttpFactory.connectionCountInClient,
		MaxIdleConnsPerHost: clientHttpFactory.connectionCountInClient,
	}
	client := &http.Client{
		Transport: transport,
	}
	if clientHttpFactory.httpTimeout > 0 {
		client.Timeout = time.Second * time.Duration(clientHttpFactory.httpTimeout)
	}
	if clientHttpFactory.isUsingProxy() {
		roxyUrl, err := url.Parse(clientKey)
		if err != nil {
			return client, err
		}
		transport.Proxy = http.ProxyURL(roxyUrl)
	}
	return client, nil
}

func (clientHttpFactory *ClientHttpFactory) saveClientToCache(clientKey string, client *http.Client) {
	clientHttpFactory.mutex.Lock()
	clientHttpFactory.clientMap[clientKey] = client
	clientHttpFactory.mutex.Unlock()
}
