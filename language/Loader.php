<?php

namespace PaserFonbet\language;

class Loader
{

    private $connectTimeout = 3;
    private $timeout = 30;

    /**@var Logger*/
    private $loader;

    /**
     * Loader constructor.
     * @param Logger $loader
     */
    public function __construct(Logger $loader)
    {
        $this->loader = $loader;
    }


    /**
     * @param $url
     * @param int $counter
     * @param int $repeat
     *
     * @return string
     */
    public function load($url, $counter = 1, $repeat = 10)
    {
        if ( $counter >= $repeat ) {
            return '';
        }

        $parsedUrl = parse_url($url);

        $http_headers = [
            "Host: {$parsedUrl['host']}",
            'Connection: keep-alive',
            'Accept: application/json, text/plain, */*',
            "Origin: http://{$parsedUrl['host']}",
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.86 Safari/537.36',
            'Referer: https://betcity.by/',
            'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
        ];

        $curlOptions = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER         => false,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER     => $http_headers,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT        => $this->timeout,
//            CURLOPT_NOPROGRESS     => false,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);

        $data  = curl_exec($curl);
        $error = curl_error($curl);
        $info  = curl_getinfo($curl);

        if ( $info['http_code'] !== 200 || empty($data) || !empty($error) ) {
            return $this->load($url, ++$counter, $repeat);
        }

        return $data;
    }

}