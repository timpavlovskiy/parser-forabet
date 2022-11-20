<?php

class Curl
{
    //$host = 'live.fonbet.com';
    //$host = 'live.bkfonbet.com';
    //$host = 'live.bk-fonbet.com';
    //$host = 'live.bkfon-bet.com';

//    private $_www = 'line41.bkfon-resource.ru';
//    private $_www = 'line510.bkfon-resources.com';
//    private $_www = 'line32.bkfon-resources.com';
//    private $_www = 'line120.bkfon-resources.com';
    private $_www = 'line03.by0e87-resources.by';
//    private $_www = 'line12.bkfon-resource.ru';
//  private $_www = 'line.fbwebdn.com';
//  private $_www = 'fbla01-01.fbwebdn.net';
//  private $_www = 'line-02.ccf4ab51771cacd46d.com';

    private $_connectTimeout = 3;
    private $_timeout = 30;

    private $needToRunEvents = [];

    private $eventIds = [];

    private $paidProxyLineEnabled = false;

    private $paidProxyLiveEnabled = false;


    /**@var ProxyHelper */
    private $proxyHelper;

    /**
     * loader constructor.
     */
    public function __construct($proxyHelper = null)
    {
        $this->proxyHelper = $proxyHelper;
    }

    /**
     * @param $url
     * @param int $sleep
     * @param int $counter
     * @param int $repeat
     *
     * @return array|mixed|null
     */
    private function _load($url, $sleep = 3, $counter = 1, $repeat = 10)
    {
        if ( $counter >= $repeat ) {
            return null;
        }

        $parsedUrl = parse_url($url);

        $http_headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language: ru-BY,ru;q=0.9',
            'Cache-Control: max-age=0',
            'Connection: keep-alive',
            "Host: {$parsedUrl['host']}",
//            "Origin: http://{$parsedUrl['host']}",
//            'Referer: https://betcity.by/',
            '" Not A;Brand";v="99", "Chromium";v="99", "Google Chrome";v="99"',
            'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="99", "Google Chrome";v="99"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Linux"',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Upgrade-Insecure-Requests: 1',
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.86 Safari/537.36',
        ];

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => $http_headers,
            CURLOPT_CONNECTTIMEOUT => $this->_connectTimeout,
            CURLOPT_TIMEOUT => $this->_timeout,
//            CURLOPT_NOPROGRESS     => false,
        ];

        if ($this->isPaidProxyLineEnabled()) {
            $proxy = $this->proxyHelper->getProxy();
            $curlOptions[CURLOPT_PROXY] = $proxy;
        } else {
            if ($this->isPaidProxyLiveEnabled()) {
                $proxy = $this->proxyHelper->getProxy();
                $curlOptions[CURLOPT_PROXY] = $proxy;
            }
        }

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);

        $data  = curl_exec($curl);
        $error = curl_error($curl);
        $info  = curl_getinfo($curl);

        curl_close($curl);

        $out = json_decode($data, true);
        $out = $out ?? [];

        if ($info['http_code'] !== 200 || empty($out) || !empty($error)) {
            if (isset($proxy)) {
                $this->proxyHelper->failProxy($proxy);
            }

            return $this->_load($url, $sleep, ++$counter, $repeat);
        }

        return $out;
    }

    public function loadLive()
    {
        $time = microtime(true);
        printf("[%s] Info: Получение лайва \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

        $this->enablePaidLiveProxy();

        $url = "http://{$this->_www}/events/list?lang=";

        $out = $this->_load($url . 'ru', 0);

        if (empty($out)) {
            printf(
                "[%s] Info: Получение лайва \t(неудача) (%f) %s",
                date('d/M/Y H:i:s'),
                microtime(true) - $time,
                PHP_EOL
            );
            return $out;
        }

        if (PARSER_NEW_API_ENABLE) {
            $shName = 'fb-live-el.sh';
            $ranEvent = [];

            if (!GOLANG_LOADER_LIVE_ENABLE) {
                exec("ps aux | grep {$shName}", $processList);
                $regex = "/^.*?(\d+).*?{$shName}\s*(\d+)$/m";
            }

            $liveEvents = [];
            if (!empty($out['events'])) {
                foreach ($out['events'] as $event) {
                    if ($event['level'] === 1 && $event['place'] === 'live' && $event['rootKind'] === 1) {
                        $liveEvents[$event['id']] = $event['id'];
                    }
                }
            }

            $dbEvents = $this->getNeedToRunEvents();
            $eventIds = array_intersect_key($dbEvents, $liveEvents);

            if (GOLANG_LOADER_LIVE_ENABLE) {
                $this->eventIds = $eventIds;
                printf(
                    "[%s] Info: Получение лайва \t(финиш) \t(%f) %s",
                    date('d/M/Y H:i:s'),
                    microtime(true) - $time,
                    PHP_EOL
                );
                return $out;
            }
        }
    }

    public function loadLine()
    {
        $time = microtime(true);
        printf("[%s] Получение линии \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

//        $url = "http://{$this->_resourceUrl}/events/list";
        if (PARSER_NEW_API_ENABLE) {
            $url = "http://{$this->_www}/events/list";
        } else {
            $url = "http://line32.bkfon-resources.com/live/currentLine/";
        }
        $out = $this->_load($url . '?lang=ru');

        if (empty($out)) {
            printf("[%s] Получение линии \t(неудача) (%f) %s", date('d/M/Y H:i:s'), microtime(true) - $time, PHP_EOL);
            return $out;
        }

        if (GOLANG_LOADER_LINE_ENABLE) {
            $remoteEventIds = [];
            if (!empty($out['events'])) {
                foreach ($out['events'] as $event) {
                    if ($event['level'] === 1 && $event['place'] === 'line' && $event['rootKind'] === 1) {
                        $remoteEventIds[$event['id']] = $event['id'];
                    }
                }
            }

            $dbEventIds = $this->getNeedToRunEvents();
            $eventIds = array_intersect_key($dbEventIds, $remoteEventIds);
            $this->eventIds = $eventIds;
        }

        printf("[%s] Получение линии \t(финиш) \t(%f) %s", date('d/M/Y H:i:s'), microtime(true) - $time, PHP_EOL);
        return $out;
    }

    public function loadResults($date = null)
    {
        $time = microtime(true);
        printf("[%s] Info: Получение результатов \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

        $url = "https://clientsapi04.by0e87-resources.by/results/results.json.php?locale=ru";

        if( !is_null($date) ) {
            $url .= "&lineDate=$date";
        }

        $out = $this->_load($url);

        if( empty($out) ) {
            printf("[%s] Info: Получение результатов \t(неудача) \t(%f) %s", date('d/M/Y H:i:s'), microtime(true) - $time, PHP_EOL);
            return $out;
        }

        printf("[%s] Info: Получение результатов \t(финиш) \t(%f) %s", date('d/M/Y H:i:s'), microtime(true) - $time, PHP_EOL);
        return $out;
    }


    /**
     * @return void
     */
    public function enablePaidLineProxy()
    {
        $this->paidProxyLineEnabled = true;
    }

    /**
     * @return void
     */
    public function enablePaidLiveProxy()
    {
        $this->paidProxyLiveEnabled = true;
    }

    /**
     * @return bool
     */
    public function isPaidProxyLineEnabled()
    {
        return $this->paidProxyLineEnabled === true;
    }

    /**
     * @return bool
     */
    public function isPaidProxyLiveEnabled()
    {
        return $this->paidProxyLiveEnabled === true;
    }

    /**
     * @return array
     */
    public function getNeedToRunEvents(): array
    {
        return $this->needToRunEvents;
    }

    /**
     * @param array $needToRunEvents
     */
    public function setNeedToRunEvents(array $needToRunEvents)
    {
        $this->needToRunEvents = $needToRunEvents;
    }

    /**
     * @return array
     */
    public function getEventIds(): array
    {
        return $this->eventIds;
    }
}

