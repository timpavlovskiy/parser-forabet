<?php

class Helper
{
    private static $blackList = [];

    private static $whiteList = [];



	/**
	 * @param $lang
	 *
	 * @return mixed
	 */
	public static function getFonbetData($lang)
	{
		//$host = 'live.fonbet.com';
		//$host = 'live.bkfonbet.com';
		//$host = 'live.bk-fonbet.com';
		//$host = 'live.bkfon-bet.com';

		$hosts = [
			'ru' => [
//                'line.fbwebdn.com',
//                'fbla01-01.fbwebdn.net',
                    'line12.bkfon-resource.ru',
//                'line-02.ccf4ab51771cacd46d.com',
			],
			'en' => [
//                'line.fbwebdn.com',
//                'fbla01-01.fbwebdn.net',
                    'line12.bkfon-resource.ru',
//                'line-01.ccf4ab51771cacd46d.com',
			],
		];

		$hosts    = $hosts[$lang] ?? [];
		$channels = [];

		$multi = curl_multi_init();

		foreach ( $hosts as $host ) {

			$address = gethostbyname($host);

			$url = 'https://' . $host . '/live/currentLine/' . $lang . '/?' . (microtime(true) / getrandmax());

			$http_headers = [
				"Host: {$host}",
				'Connection: keep-alive',
				'Cache-Control: max-age=0',
				'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36',
				'Accept: */*',
				"Referer: https://{$host}/",
				'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
			];

			$ch = curl_init();

			curl_setopt_array($ch, [
				CURLOPT_URL            => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER     => $http_headers,
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_ENCODING       => '',
				CURLOPT_TIMEOUT        => 3,
				// CURLOPT_TIMEOUT_MS => 5,
				//                CURLOPT_PROXY          => '127.0.0.1:9050',
				//                CURLOPT_PROXYTYPE      => CURLPROXY_SOCKS5,
			]);

			curl_multi_add_handle($multi, $ch);

			$channels[$host] = $ch;
		}

		/**
		 * Собираем результаты отроботанных потоков
		 */
		$results = [];

		/**
		 * Маркер для успешной загрузки данных хотя бы на одном потоке
		 */
		$finished = false;

		/**
		 * Количество рабочих потоков
		 */
		$running = null;

		do {
			curl_multi_exec($multi, $running);

			/**
			 * Получаем информацию о потоке
			 */
			$info = curl_multi_info_read($multi);

			if ( $info !== false ) {

				/**
				 * Если поток завершился
				 */
				if ( $info['msg'] === CURLMSG_DONE ) {
					$ch = $info['handle'];

					$url     = array_search($ch, $channels);
					$content = curl_multi_getcontent($ch);

					if ( !empty($content) ) {
						$results[$url] = $content;

						$finished = true;
					}

					/**
					 * Закрываем поток
					 */
					curl_multi_remove_handle($multi, $ch);
					curl_close($ch);
				}
			}
		} while ( $running > 0 && $finished === false );

		curl_multi_close($multi);

		return $results;
	}

	/**
	 * Забираем данные со страницы "Результаты"
	 *
	 * @param null $date
	 *
	 * @return mixed
	 */
	public static function getFonbetResults($date = null)
	{
		$url = 'https://clientsapi16.bkfon-resource.ru/results/results.json.php?locale=ru';

		if ( !is_null($date) ) {
			$url .= '&lineDate=' . $date; // 2018-11-07
		}

		$http_headers = [
			"Host: {$url}",
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36',
			'Accept: */*',
			'Referer: https://clientsapi16.bkfon-resource.ru/',
			'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
		];

		$ch = curl_init();

		curl_setopt_array($ch, [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => $http_headers,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_ENCODING       => '',
			CURLOPT_TIMEOUT        => 3,
			//            CURLOPT_TIMEOUT_MS     => 5,
			//            CURLOPT_PROXY          => '127.0.0.1:9050',
			//            CURLOPT_PROXYTYPE      => CURLPROXY_SOCKS5,
		]);

		$data = curl_exec($ch);

		$info = curl_getinfo($ch);

		curl_close($ch);

		return $data;
	}

	/**
	 * Генерация хэша для турниров
	 *
	 * @param int    $sportId
	 * @param string $name
	 *
	 * @return string
	 */
	public static function leagueHash($sportId, $name)
	{
	    $sportId = (int) $sportId;
        $name = trim($name);

		return md5($sportId . '@' . $name);
	}

    /**remote_id
     * Генерация хэшов для событий
     *
     * @param int    $sportId
     * @param string $date Y-m-d
     * @param string $team1
     * @param string $team2
     *
     * @return string
     */
    public static function eventHash($sportId, $date, $team1, $team2)
    {
        $sportId = (int) $sportId;
        $team1 = trim($team1);
        $team2 = trim($team2);

        return md5($sportId . '@' . $date . '@' . $team1 . '@' . $team2);
    }

    /**
     * Генерирует хэш команды
     *
     * @param $sportId
     * @param $name
     *
     * @return string
     */
    public static function getTeamHash($sportId, $name)
    {
        $sportId = (int) $sportId;
        $name = trim($name);

        return md5($sportId . '@' . $name);
    }

    /**
     * @param array $blackList
     */
    public static function setBlackList($blackList)
    {
        self::$blackList = $blackList;
    }

    public static function hasInBlackList($sportId, $string)
    {
        $blackList = self::$blackList;

        foreach ( $blackList as $item ) {
            if ( $item['sportId'] !== $sportId ) {
                continue;
            }

            if ( empty($string) || empty($item['value']) ) {
                continue;
            }

            if ( mb_stripos($string, $item['value'], 0, 'utf8') !== false ) {
                return $item;
            }
        }

        return [];
    }

    /**
     * @param array $whiteList
     */
    public static function setWhiteList($whiteList)
    {
        self::$whiteList = $whiteList;
    }

    public static function hasInWhiteList($sportId, $string)
    {
        $whiteList = self::$whiteList;

        foreach ( $whiteList as $item ) {
            if ( $item['sportId'] !== $sportId ) {
                continue;
            }

            if ( empty($string) || empty($item['value']) ) {
                continue;
            }

            if ( mb_stripos($string, $item['value'], 0, 'utf8') !== false ) {
                return $item;
            }
        }

        return [];
    }

    /**
     * Реиндексирование коэффициентов
     *
     * @param $data
     * @return array
     */
    public static function reindexFactors($data)
    {
        if( empty($data) ) {
            return $data;
        }

        $blockedEvents = [];

        // TODO: частичная блокировка кэфов
//    [
//        eventId: 111111,
//        state: 'partial',
//        factors => [927]
//    ]

        foreach ( $data['eventBlocks'] as $event ) {
            if ( $event['state'] === 'blocked' ) {
                $blockedEvents[$event['eventId']] = true;
            }

        }

        $factorList = [];

        if (PARSER_NEW_API_ENABLE) {
            foreach ($data['customFactors'] as $item) {
                $eventId = $item['e'];
                $factors = $item['factors'];

                $blocked = $blockedEvents[$eventId] ?? false;

                foreach ($factors as $factor) {
                    if ($factor['v'] < 1) {
                        continue;
                    }

                    $factorList[$eventId][$factor['f']] = [
                        'e' => $eventId,
                        'f' => $factor['f'],
                        'pt' => $factor['pt'] ?? null,
                        'v' => $factor['v'],
                        'b' => $blocked,
                    ];
                }
            }
        } else {
            foreach ($data['customFactors'] as $factor) {
                if ($factor['v'] < 1) {
                    continue;
                }

                $eventId = $factor['e'];
                $blocked = $blockedEvents[$eventId] ?? false;
                $factorList[$factor['e']][$factor['f']] = [
                    'e' => $eventId,
                    'f' => $factor['f'],
                    'pt' => $factor['pt'] ?? null,
                    'v' => $factor['v'],
                    'b' => $blocked,
                ];
            }
        }

        ksort($factorList);
        return $factorList;
    }

    /**
     * Сортировка данных и добовления ключа order
     *
     * @param $data
     * @return mixed
     */
    public static function customSort($data)
    {
        if( empty($data) ) {
            return $data;
        }

        usort($data, function ($v1, $v2) {
            if ( $v1['sortOrder'] === $v2['sortOrder'] ) {
                return 0;
            }

            return $v1['sortOrder'] > $v2['sortOrder'] ? 1 : -1;
        });

        $count = count($data);

        foreach ( $data as $k => $v ) {
            $data[$k]['order'] = $count - $k;
        }

        return $data;
    }

    /**
     * Реиндексирование данных
     *
     * @param $arr
     * @param string $key
     * @return array
     */
    public static function reindexByKey($arr, $key = 'id')
    {
        if( empty($arr) ) {
            return $arr;
        }

        $out = [];

        foreach ( $arr as $item ) {
            $out[$item[$key]] = $item;
        }

        ksort($out);
        return $out;
    }
}