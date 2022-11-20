<?php

class Parser
{
    private $_sportMapper = [];

    private $_ignoreSports = [];

    private $_countryMapper = [];

    private $_periodNames = [];

    private $_contextMapper = [];

	private $_leagues = [];

	private $_events = [];

    private $_newLeagues = [];

    private $_newEvents  = [];

    private $_newTeams   = [];

    private $_newResults = [];

    private $_newVideos = [];

    private $_updateLeagues = [];

    private $_updateEvents  = [];

    private $_updateTeams   = [];

    private $_updateResults = [];

    private $_updateVideos = [];


    /**
     * Parser constructor.
     * @param $sportMapper
     * @param $ignoreSports
     * @param $countryMapper
     */
    public function __construct($sportMapper, $ignoreSports, $countryMapper)
	{
        $this->_sportMapper = $sportMapper;
        $this->_ignoreSports = $ignoreSports;
        $this->_countryMapper = $countryMapper;

        $this->_periodNames = [
            'сет',
            'тайм',
            'карта',
            'период',
            'четверть',
        ];

        $this->_contextMapper = [
            'угловые'                => Factors::CONTEXT_CORNERS,
            'жёлтые карты'           => Factors::CONTEXT_YELLOW_CARDS,
            'желтые карты'           => Factors::CONTEXT_YELLOW_CARDS,
            'овертайм'               => Factors::CONTEXT_OVERTIME,
            'серия буллитов'         => Factors::CONTEXT_SERIES_BULLITES,
            'эйсы'                   => Factors::CONTEXT_ACE,
            'дополнительное время'   => Factors::CONTEXT_ADD_TIME,
//            'Доп.время'              => Factors::CONTEXT_ADD_TIME,
            'Доп.время угловые'      => Factors::CONTEXT_CORNERS_ADD_TIME,
            'Доп.время желтые карты' => Factors::CONTEXT_YELLOW_CARDS_ADD_TIME,
            'серия пенальти'         => Factors::CONTEXT_SERIES_PENALTY,
            'заб/3-х очковые'        => Factors::CONTEXT_THREE_POINT,
            'кол-во 2-мин удалений'  => Factors::CONTEXT_TWO_MIN_REMOVAL,
            'ошибки на подаче'       => Factors::CONTEXT_ERRORS_ON_FILLING,
            'броски в створ'         => Factors::CONTEXT_SHOTS_ON_GOAL,
            'удары в створ'          => Factors::CONTEXT_SHOTS_ON_GOAL,
            'фолы'                   => Factors::CONTEXT_FOULS,
            'удары от ворот'         => Factors::CONTEXT_GOAL_KICKS,
            'голы в больш'           => Factors::CONTEXT_POWER_PLAY_GOALS,
            'офсайды'                => Factors::CONTEXT_OFFSIDES,
            'вбрасывания'            => Factors::CONTEXT_FACE_OFFS,
            'вброс аутов'            => Factors::CONTEXT_THROW_INS
        ];
	}

    public function parseLeaguesResult($type, $data)
    {
        $sports = $data['sections'] ?? [];

        switch ($type) {
            case LIVE_CONTENT_NAME: $parserId = PARSER_LIVE_ID; break;
            case LINE_CONTENT_NAME: $parserId = PARSER_LINE_ID; break;
            default:
                throw new Exception("Неизвестный тип контента {$type}");
        }

        $out = [];
        ksort($sports);

        $matchIgnorePattern = '#' . implode('|', array_keys($this->_ignoreSports)) . '#su';

        foreach ( $sports as $sport ) {
            $sport['name'] = preg_replace('#\s#u', ' ', $sport['name']);
            $hasMatch = preg_match($matchIgnorePattern, $sport['name']);

            if( !empty($hasMatch) ) {
                continue;
            }

            $decoded = $this->_decodeSport($sport['name']);

            if ( empty($decoded) ) {
                printf("[%s] Notice: Не могу распарсить турнир: %s %s", date('d/M/Y H:i:s'), $sport['name'], PHP_EOL);
                continue;
            }

            $localSport = $this->_sportMapper[$decoded['sportName']];
            $sportParserId = 0;

            switch ($type) {
                case LIVE_CONTENT_NAME: $sportParserId = $localSport['parserLive']; break;
                case LINE_CONTENT_NAME: $sportParserId = $localSport['parserPrematch']; break;
                default:
                    throw new Exception("Неизвестный тип контента {$type}");
            }

            if ($sportParserId !== $parserId) {
                continue;
            }

            $eventIds = [];

            foreach ( $sport['events'] as $eventId ) {
                $eventIds[$eventId] = $eventId;
            }

            $out[$sport['id']] = array_merge($decoded, [
                'remoteId' => $sport['id'],
                'events'   => $eventIds,
            ]);
        }

        $this->_leagues = $out;
        return $out;
    }

    public function parseEventsResult($data)
    {
        $events = $data['events'] ?? [];

        $out = [];
        ksort($events);

        foreach ( $events as $event ) {
            $eventId = $event['id'];
            $_league = [];

            foreach ($this->_leagues as $league ) {
                if( isset($league['events'][$eventId]) ) {
                    $_league = $league;
                    break;
                }
            }

            if( empty($_league) ) {
                continue;
            }

            $out[$eventId] = array_merge($event, [
                'sportId'    => $_league['sportId'],
                'sportName'  => $_league['sportName'],
                'leagueName' => $_league['leagueName'],
            ]);
        }

        $this->_events = $out;
        return $out;
    }

	public function parserLeaguesAnons($type, $data)
	{
        $sports = $data['announcements'] ?? [];

        switch ($type) {
            case LIVE_CONTENT_NAME: $parserId = PARSER_LIVE_ID; break;
            case LINE_CONTENT_NAME: $parserId = PARSER_LINE_ID; break;
            default:
                throw new Exception("Неизвестный тип контента {$type}");
        }

        $out = [];
        ksort($sports);

        $matchIgnorePattern = '#' . implode('|', array_keys($this->_ignoreSports)) . '#su';

		foreach ( $sports as $sport ) {
            $sport['segmentName'] = preg_replace('#\s#u', ' ', $sport['segmentName']);
            $hasMatch = preg_match($matchIgnorePattern, $sport['segmentName']);

            if( !empty($hasMatch) ) {
                continue;
            }

			$decoded = $this->_decodeSport($sport['segmentName']);

            if ( empty($decoded) ) {
                printf("[%s] Notice: Не могу распарсить турнир: %s %s", date('d/M/Y H:i:s'), $sport['segmentName'], PHP_EOL);
                continue;
            }

            $localSport = $this->_sportMapper[$decoded['sportName']];
            $sportParserId = 0;

            switch ($type) {
                case LIVE_CONTENT_NAME: $sportParserId = $localSport['parserLive']; break;
                case LINE_CONTENT_NAME: $sportParserId = $localSport['parserPrematch']; break;
                default:
                    throw new Exception("Неизвестный тип контента {$type}");
            }

            if ($sportParserId !== $parserId) {
                continue;
            }

            $out[$sport['segmentId']] = array_merge($decoded, [
               'remoteId' => $sport['segmentId'],
               'order'    => $sport['order'] ?? 0,
               'regionId' => $sport['regionId'] ?? 1,
            ]);
		}

		$this->_leagues = $out;
		return $out;
	}

	public function parseEventsAnons($data)
	{
        $events = $data['announcements'] ?? [];

        $out = [];
        ksort($events);

		$timeNow = time();

		foreach ( $events as $event ) {
			if ( !isset($this->_leagues[$event['segmentId']]) ) {
				continue;
			}

			if ( $event['startTime'] < $timeNow ) {
				continue;
			}

            $league = $this->_leagues[$event['segmentId']];
            $sportId = $league['sportId'];

            $out[$event['id']] = array_merge($event, [
                'sportId'    => $league['sportId'],
                'sportName'  => $league['sportName'],
                'leagueId'   => $event['segmentId'],
                'leagueName' => $league['leagueName'],
            ]);
		}

		$this->_events = $out;
		return $out;
	}

    public function parseLeagues($type, $data)
	{
		$sports = $data['sports'] ?? [];

        switch ($type) {
            case LIVE_CONTENT_NAME: $parserId = PARSER_LIVE_ID; break;
            case LINE_CONTENT_NAME: $parserId = PARSER_LINE_ID; break;
            default:
                throw new Exception("Неизвестный тип контента {$type}");
        }


        $out = [];
        ksort($sports);

        $matchIgnorePattern = '#' . implode('|', array_keys($this->_ignoreSports)) . '#su';

		foreach ( $sports as $sport ) {
			if ( array_key_exists('parentId', $sport) === false ) {
				continue;
			}

            $sport['name'] = preg_replace('#\s#u', ' ', $sport['name']);
            $hasMatch = preg_match($matchIgnorePattern, $sport['name']);

            if( !empty($hasMatch) ) {
                continue;
            }

			$decoded = $this->_decodeSport($sport['name']);

            if ( empty($decoded) ) {
                printf("[%s] Notice: Не могу распарсить турнир: %s %s", date('d/M/Y H:i:s'), $sport['name'], PHP_EOL);
                continue;
            }

            $localSport = $this->_sportMapper[$decoded['sportName']];
            $sportParserId = 0;

            switch ($type) {
                case LIVE_CONTENT_NAME: $sportParserId = $localSport['parserLive']; break;
                case LINE_CONTENT_NAME: $sportParserId = $localSport['parserPrematch']; break;
                default:
                    throw new Exception("Неизвестный тип контента {$type}");
            }

            if ($sportParserId !== $parserId) {
                continue;
            }

            $out[$sport['id']] = array_merge($decoded, [
               'remoteId' => $sport['id'],
               'order'    => $sport['order'],
               'regionId' => $sport['regionId'] ?? 1
            ]);
		}

		$this->_leagues = $out;
		return $out;
	}

    public function parseEvents($data, $type)
	{
        $fonbetEvents = $data['events'] ?? [];
		$eventByTeams = [];

		foreach ( $fonbetEvents as $fbEventId => $event ) {
			if ( !empty($event['team1Id']) && !empty($event['team2Id']) ) {
				$keyTeams = "{$event['sportId']}:{$event['team1Id']}:{$event['team2Id']}";

                $fonbetEvents[$fbEventId]['keyTeams'] = $keyTeams;
                $eventByTeams[$keyTeams][] = $event;
			}
		}

        /**
         * Проверяем есть ли дубликаты у событий
         */
		foreach ( $eventByTeams as $keyTeam => $events ) {
            if( count($events) === 1 ) {
                continue;
            } else {
                uasort($events, function($v1, $v2) {
                    return $v1['id'] < $v2['id'] ? -1 : 1;
                });
            }

            $rootEvent = $events[0];

            foreach ( $events as $event ) {
                /**
                 * Это дубликат, присвоим ему ключ realId с id оригинального события
                 */
                if ( $rootEvent['id'] !== $event['id'] ) {
                    $fonbetEvents[$event['id']]['realId'] = $rootEvent['id'];

                    /**
                     * Является ли это событие внутренними
                     *
                     * IamPoint Club — Avalon
                     * 1-я карта IamPoint Club — Avalon
                     */
                     $isNested = preg_match('#^(\d+)\-#isu', $event['name'], $match);

                     if( $isNested ) {
                         $fonbetEvents[$event['id']]['level']    = 2;
                         $fonbetEvents[$event['id']]['parentId'] = $rootEvent['id'];
                     }
                }
            }
        }

        $out = [];
        ksort($fonbetEvents);

		foreach ( $fonbetEvents as $fbEventId => $event ) {
            if( !empty($event['place']) ) {
                if( $type === LIVE_CONTENT_NAME && $event['place'] !== 'live' ) {
                    continue;
                }

                if( $type === LINE_CONTENT_NAME && $event['place'] !== 'line' ) {
                    continue;
                }
            }

		    if ( !isset($this->_leagues[$event['sportId']]) ) {
				continue;
			}

            $league = $this->_leagues[$event['sportId']];
			$sportId = $league['sportId'];

            /**
             * Не парсим половины для баскетбола
             */
            if ( $sportId == 2 && mb_strpos($event['name'], 'половина') ) {
				continue;
			}

            $event['comment'] = $event['comment'] ?? '';

            $event = array_merge($event, [
                'context'    => Factors::CONTEXT_MAIN,
                'period'     => 0,
                'sportId'    => $league['sportId'],
                'sportName'  => $league['sportName'],
                'leagueId'   => $event['sportId'],
                'leagueName' => $league['leagueName'],
            ]);

            if ( !isset($event['timerSeconds']) ) {
                $event['timerSeconds'] = 0;
            }

            /**
             * Собираем кэфы
             */
            if ( array_key_exists($event['id'], $data['customFactors']) ) {
				$event['factors'] = $data['customFactors'][$event['id']];
			}

            /**
             * Подклеиваем счёт и комменты
             */
			if ( array_key_exists($event['id'], $data['eventMiscs']) ) {
				$event = array_merge($event, $data['eventMiscs'][$event['id']]);
			}

		    if( isset($event['parentId']) ) {
                $parent = $fonbetEvents[$event['parentId']];

                /**
                 * Если у родителя определён ключ realId, тогда ссылку на парента у дочернего сменим
                 */
                if ( isset($parent['realId']) ) {
                    if ( $parent['name'] !== '' && $event['name'] !== '' ) {
                        $event['name'] = $parent['name'] . ' ' . $event['name'];
                    }

                    $fonbetEvents[$fbEventId]['parentId'] = $event['parentId'] = $parent['realId'];
                }
            }

            /**
             * Настольные теннис переопределение имени турнира
             */
            if ( $sportId === 15 ) {
                if ( mb_stripos($event['comment'], '5сетов', 0, 'utf8') !== false ) {
                    $event['leagueName'] .= '. Матч из 5 сетов';
                }
            }

			/**
			 * Подклеиваем имя ко второй команде
			 */
			if ( $event['level'] === 1 && $event['name'] !== '' ) {
                $event['team2'] = $event['team2'] ?? '';
				$event['team2'] = "{$event['team2']} ({$event['name']})";
			}

			if ( array_key_exists('parentId', $event) ) {
                if ( isset($out[$event['parentId']]) ) {
                    $period = $this->getPeriod($event['name']);
                    $context = $this->getContext($event['name']);

                    if (empty($context)) {
                        $isMainContext = $this->isMainContext($event['name']);

                        if( $isMainContext ) {
                            $context = Factors::CONTEXT_MAIN;
                        } else {
                            printf("[%s] Notice: Неизвестный контекст: %s %s", date('d/M/Y H:i:s'), $event['name'], PHP_EOL);
                            continue;
                        }
                    }

                    $event['period'] = $period;
                    $event['context'] = $context;

                    $out[$event['parentId']]['periods'][$event['id']] = $event;
                }
			} else {
                $out[$event['id']] = $event;
                $out[$event['id']]['periods'][0] = $event;
			}
		}

		$this->_events = $out;
		return $out;
	}

    private function _decodeSport($source)
    {
        $matchSportPattern = '#^(' . implode('|', array_keys($this->_sportMapper)) . ')\.#su';

        $sportName = '';
        $hasMatch = false;

        do {
            $hasMatch = preg_match($matchSportPattern, $source, $match) > 0;

            if( $hasMatch ) {
                $sportName = $match[1];
                $source = preg_replace('#(' . $sportName . ')\.\s?#su', '', $source);
            }

        } while ( $hasMatch === true );

        if( !isset($this->_sportMapper[$sportName]) ) {
            return [];
        }

        $sport = $this->_sportMapper[$sportName];
        $isCyber = $sport['cyber'] ?? false;

        $source = preg_replace(['/Жен\./isu', '/Wom\./isu', '/Муж\./isu'], ['Женщины.', 'Women.', 'Мужчины.'], $source);

        /**
         * Турнир должен заканчиваться символом '.'
         */
        if( !preg_match('#\.$#', $source) ) {
            $source = $source . '.';
        }

        /**
         * Для кибер дисциплин в название турнира дописываем (киберфутбол, киберхоккей, кибертеннис)
         */
        if( $isCyber ) {
            $source = $sportName . '. ' . $source;
        }

        return ['sportId' => $sport['id'], 'sportName' => $sport['name'], 'leagueName' => $source];

//        if ( $sportId === 4 ) {
//            if ( preg_match('/КХЛ|НХЛ|KHL|NHL/iu', $league) > 0 && preg_match('/время|time/iu', $league) === 0 ) {
//                if ( $this->_lang === 'ru' ) {
//                    $league = trim($league, '. ') . '. Основное время.';
//                }
//
//                if ( $this->_lang === 'en' ) {
//                    $league = trim($league, '. ') . '. Regular time.';
//                }
//            }
//        }
    }

    private function getPeriod($str)
	{
        $hasMatch = preg_match('#^(\d)-?(?:й|я|st|nd|rd|th)#iu', $str, $matches);

        if( !empty($hasMatch) ) {
            return $matches[1] ?? 0;
        }

        return null;
	}

    private function isMainContext($str)
    {
        $pattern = '#(' . implode('|', $this->_periodNames) . ')#su';
        $hasMatch = preg_match($pattern, $str, $match);

        if( !empty($hasMatch) ) {
            return true;
        }

        return false;
    }

	private function getContext($str)
	{
        $pattern = '#(' . implode('|', array_keys($this->_contextMapper)) . ')#su';
        $hasMatch = preg_match($pattern, $str, $match);

        if( !empty($hasMatch) ) {
            return $this->_contextMapper[$match[1]];
        }

        return null;
	}

    /**
     * Получение списка турниров которые нужно добавить
     * @return array
     */
    public function getNewLeagues()
    {
        return $this->_newLeagues;
    }

    /**
     * Получение списка турниров которые нужно обновить
     * @return array
     */
    public function getUpdateLeagues()
    {
        return $this->_updateLeagues;
    }

    /**
     * Обробатываем турниры
     *
     * @param $remoteLeagues
     * @param $leaguesByIds
     * @param $leaguesByHashes
     */
    public function processLeagues($remoteLeagues, $leaguesByIds, $leaguesByHashes)
    {
        foreach ( $remoteLeagues as $league ) {
            $leagueById = $leaguesByIds[$league['idRemote']] ?? null;
            $leagueByHash = $leaguesByHashes[$league['hashRemote']] ?? null;

            if( is_null($leagueById) && is_null($leagueByHash) ) {
                $this->_newLeagues[$league['idRemote']] = $league;
                continue;
            }

            $newHash = Helper::leagueHash($league['sportId'], $league['name']);

            $localLeague     = $leagueById ?? $leagueByHash;
            $localLeagueId   = $localLeague['id'];
            $localLeagueHash = $localLeague['remoteHash'];

            if( $newHash === $localLeagueHash ) {
                if( $league['idRemote'] !== $localLeague['remoteId'] ) {
                    $this->_updateLeagues[$localLeagueId]['remote_id'] = $league['idRemote'];
                }
            } else {
                $this->_updateLeagues[$localLeagueId]['remote_hash'] = $newHash;
            }

            if( $league['name'] !== $localLeague['name'] ) {
                $this->_updateLeagues[$localLeagueId]['name'] = $league['name'];
            }
        }
    }

    /**
     * Получение списка турниров которые нужно добавить
     * @return array
     */
    public function getNewEvents()
    {
        return $this->_newEvents;
    }

    /**
     * Получение списка турниров которые нужно обновить
     * @return array
     */
    public function getUpdateEvents()
    {
        return $this->_updateEvents;
    }

    /**
     * Обробатываем события
     *
     * @param $remoteEvents
     * @param $eventsByIds
     * @param $eventsByHashes
     */
    public function processEvents($remoteEvents, $eventsByIds, $eventsByHashes)
    {
        foreach ( $remoteEvents as $event ) {
            $eventById = $eventsByIds[$event['idRemote']] ?? null;
            $eventByHash = $eventsByHashes[$event['hashRemote']] ?? null;

            if( is_null($eventById) && is_null($eventByHash) ) {
                $this->_newEvents[$event['idRemote']] = $event;
                continue;
            }

            $newHash = Helper::eventHash($event['sportId'], $event['date'], $event['team1'], $event['team2']);

            $localEvent     = $eventById ?? $eventByHash;
            $localEventId   = $localEvent['id'];
            $localEventHash = $localEvent['remoteHash'];

            if( $newHash === $localEventHash ) {
                if( $event['idRemote'] !== $localEvent['remoteId'] ) {
                    $this->_updateEvents[$localEventId]['remote_id'] = $event['idRemote'];
                }
            } else {
                $this->_updateEvents[$localEventId]['remote_hash'] = $newHash;
            }

            if( $event['date'] !== $localEvent['time'] ) {
                $this->_updateEvents[$localEventId]['time'] = $event['date'];
            }

            if( $event['team1'] !== $localEvent['team1'] ) {
                $this->_updateEvents[$localEventId]['first'] = $event['team1'];
            }

            if( $event['team2'] !== $localEvent['team2'] ) {
                $this->_updateEvents[$localEventId]['second'] = $event['team2'];
            }
        }
    }

    /**
     * Получение списка команд которые нужно добавить
     * @return array
     */
    public function getNewTeams()
    {
        return $this->_newTeams;
    }

    /**
     * Получение списка команд которые нужно обновить
     * @return array
     */
    public function getUpdateTeams()
    {
        return $this->_updateTeams;
    }

    /**
     * Обробатываем команды
     *
     * @param $remoteTeams
     * @param $teamsByHashes
     */
    public function processTeams($remoteTeams, $teamsByHashes)
    {
        foreach ( $remoteTeams as $index => $team ) {
            $teamByHash = $teamsByHashes[$team['hashRemote']] ?? null;

            if( is_null($teamByHash) ) {
                $this->_newTeams[$team['hashRemote']] = $team;
                continue;
            }

            $localTeamId = $teamByHash['id'];

            if( $teamByHash['disabled'] === 1 ) {
                $this->_updateTeams[$localTeamId]['disabled'] = 0;
            }
        }
    }

    /**
     * Получение списка результатв которые нужно добавить
     * @return array
     */
    public function getNewResults()
    {
        return $this->_newResults;
    }

    /**
     * Получение списка результатов которые нужно обновить
     * @return array
     */
    public function getUpdateResults()
    {
        return $this->_updateResults;
    }

    /**
     * Обробатываем результаты
     *
     * @param $events
     * @param $remoteResults
     * @param $localResults
     */
    public function processResults($events, $remoteResults, $localResults)
    {
        foreach ( $remoteResults as $remoteId => $results ) {
            if( !isset($events[$remoteId]) ) {
                continue;
            }

            $event = $events[$remoteId];
            $eventId = $event['id'];

            $oldResult = $localResults[$eventId] ?? [];

            foreach ( $results as $name => $content ) {
                /**
                 * Если у локальных результатов уже есть ключ finished
                 * тогда не зависимо от значения не обновляем это поле
                 */
                if( $name === 'finished' && isset($oldResult[$name]) ) {
                    continue;
                }

                if( !isset($oldResult[$name]) ) {
                    $this->_newResults[$eventId][$name] = $content;
                    continue;
                }

                /**
                 * Если результаты идентичны тогда их не добовляем в список на обновление результатов
                 */
                if( $this->_isIdentity($name, $content, $oldResult[$name]) === true ) {
                    continue;
                }

                $this->_updateResults[$eventId][$name] = $content;
            }
        }
    }

    /**
     * Обробатываем видеотрансляции
     *
     * @param $events
     * @param $remoteVideos
     * @param $localVideos
     */
    public function processVideos($events, $remoteVideos, $localVideos)
    {
        foreach ( $remoteVideos as $remoteId => $video ) {
            if( !isset($events[$remoteId]) ) {
                continue;
            }

            $event = $events[$remoteId];
            $eventId = $event['id'];

            $localVideo = $localVideos[$eventId] ?? null;

            if( is_null($localVideo) ) {
                $this->_newVideos[$video['idRemote']] = $video;
                continue;
            }

            $localVideoId = $localVideo['id'] ?? null;

            if( $localVideo['url'] !== $video['url'] ) {
                $this->_updateVideos[$localVideoId]['url'] = $video['url'];
            }
        }
    }

    /**
     * Получение списка видеотрансляций которые нужно добавить
     * @return array
     */
    public function getNewVideos()
    {
        return $this->_newVideos;
    }

    /**
     * Получение списка видеотрансляций которые нужно обновить
     * @return array
     */
    public function getUpdateVideos()
    {
        return $this->_updateVideos;
    }

    /**
     * Проверяем результаты на идентичность
     *
     * @param $name
     * @param $newResult
     * @param $currentResult
     * @return bool
     */
    private function _isIdentity($name, $newResult, $currentResult)
    {
        if( $name === 'timer' ) {
            return ($newResult - $currentResult < 10);
        }

        if( is_array($newResult) ) {
            $newResult = json_encode($newResult);
        }

        if( is_array($currentResult) ) {
            $currentResult = json_encode($currentResult);
        }

        if( !is_string($newResult) ) {
            $newResult = (string) $newResult;
        }

        if( !is_string($currentResult) ) {
            $currentResult = (string) $currentResult;
        }

        $newResult = preg_replace("#\"time\":\".*?\",#isu", '', $newResult);
        $currentResult = preg_replace('#\"time\":\".*?\",#isu', '', $currentResult);

        return (md5($newResult) === md5($currentResult));
    }

    /**
     * Выврезаем название страны из турнира
     *
     * @param $leagueName
     * @return string|string[]|null
     */
    public function getCountryId($leagueName)
    {
        $pattern = '#' . implode('|', array_keys($this->_countryMapper)) . '#isu';
        preg_match($pattern, $leagueName, $match);

        $countryName = $match[0] ?? '';

        if( !isset($this->_countryMapper[$countryName]) ) {
            return 1;
        }

        return $this->_countryMapper[$countryName]['id'];
    }
}