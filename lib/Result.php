<?php

class Result
{
    private static $comment = '';

    /**
     * Список ключей результатов которые являються счётом
     *
     * @var array
     */
    private static $_resultIsScore = [
        Factors::CONTEXT_MAIN,
        Factors::CONTEXT_ACE,
        Factors::CONTEXT_OVERTIME,
        Factors::CONTEXT_ADD_TIME,
        Factors::CONTEXT_CORNERS,
        Factors::CONTEXT_CORNERS_ADD_TIME,
        Factors::CONTEXT_YELLOW_CARDS,
        Factors::CONTEXT_YELLOW_CARDS_ADD_TIME,
        Factors::CONTEXT_SERIES_PENALTY,
        Factors::CONTEXT_THREE_POINT,
        Factors::CONTEXT_TWO_MIN_REMOVAL,
        Factors::CONTEXT_SERIES_BULLITES,
        Factors::CONTEXT_ERRORS_ON_FILLING,
        Factors::CONTEXT_SHOTS_ON_GOAL,
        Factors::CONTEXT_FOULS,
        Factors::CONTEXT_GOAL_KICKS,
        Factors::CONTEXT_POWER_PLAY_GOALS,
        Factors::CONTEXT_OFFSIDES,
        Factors::CONTEXT_FACE_OFFS,
        Factors::CONTEXT_THROW_INS
    ];


    public function __construct()
    {
    }

    /**
     * Декодируем результат
     *
     * @param $sportSetting
     * @param $remoteEvent
     * @param $localResult
     *
     * @return array
     */
    public function decode($sportSetting, $remoteEvent, $localResult) {

        $sportId = $remoteEvent['sportId'];

        /**
         * Получаем счёт по событию
         */
        $results['score'] = $this->_getScore($remoteEvent);

        /**
         * Выдераем подачу из счёта
         */
        $results['turn'] = (int) ($remoteEvent['servingTeam'] ?? '');

        /**
         * Получаем таймер если есть (секунды)
         */
        $results['timer'] = $remoteEvent['timerSeconds'] ?? 0;

        /**
         * Получаем текущий период
         */
        $results['period'] = $this->_getPeriod($sportId, $results);

        /**
         * Вытягиваем комменты
         */
        $results['comment'] = $this->_getComment();

        /**
         * Проверяем не закончилось ли событие
         */
        $results['finished'] = $this->_getItog($remoteEvent);

        /*
         * Запускаем перебор по внутренним событиям
         */
        if( count($remoteEvent['periods']) > 0 ) {
            /**
             * Вложенные события у которых context !== score
             * например (угловые, броски в створ ворот, заб/3-х очковые)
             */
            foreach ( $remoteEvent['periods'] as $nestedEvent ) {
                $period = (int) $nestedEvent['period'];

                /**
                 * Родительское событие, обработанно выше
                 */
                if( $nestedEvent['level'] === 1 ) {
                    continue;
                }

                if( !empty($nestedEvent['servingTeam']) ) {
                    $results['turn'] = (int) $nestedEvent['servingTeam'];
                }

                /**
                 * Если это вутренние события для тенниса (1-й сет, 2-й сет)
                 * тогда обробатываем только тот сет на который есть счёт
                 */
                if( $nestedEvent['sportId'] === 3 && $period === count($results['score']['periods']) ) {
                    $results['points'] = $this->_getPoints($nestedEvent);
                }

                /**
                 * Если это внутренние события для dota-2 или LoL
                 * тогда забираем счёт по периодам из внутренних событий и таймер
                 */
                if( $nestedEvent['sportId'] === 86 || $nestedEvent['sportId'] === 91) {
                    $results['timer'] = $nestedEvent['timerSeconds'] ?? 0;

                    $results['score']['periods'][$period - 1] = [
                        'time'   => date('Y-m-d H:i:s'), // 2018-01-11 17:55:23
                        'first'  => (int) $nestedEvent['score1'] ?? 0,
                        'second' => (int) $nestedEvent['score2'] ?? 0
                    ];
                }

                /**
                 * Если есть внутренне событие "Доп. время" тогда берём его таймер
                 */
                if( $nestedEvent['context'] === Factors::CONTEXT_ADD_TIME || $nestedEvent['context'] === Factors::CONTEXT_OVERTIME ) {
                    $results['timer'] = $nestedEvent['timerSeconds'];
                }

                /**
                 * Если не внутренее событие тогда пропускаем не учитываем 1-й сет, 2-тайм, 3-период, 4-четверть
                 */
                if( $nestedEvent['context'] === Factors::CONTEXT_MAIN ) {
                    continue;
                }

                /**
                 * Забираем результат для статистики
                 */
                if( $period === 0 ) {
                    $results[$nestedEvent['context']] = $this->_getScore($nestedEvent);
                }
            }
        }

        /**
         * Нормализуем счёт
         */
        $results = $this->_normalize($results, $localResult);

        /**
         * Собираем лог по геймам
         */
        if( $remoteEvent['sportId'] === 3 ) {
            if( !empty($results['points']) ) {
                $games = json_decode($localResult['games'] ?? '', true);
                $games = $games ?? [];

                $results['games'] = $this->_generateGames($sportSetting, $games, $results);
            }
        }

        /**
         * Генерируем лог счёта
         */
        $log = json_decode($localResult['log'] ?? '', true);
        $log = $log ?? [];

        $results['log'] = $this->_generateLog($sportSetting, $log, $results);

        /**
         * Удаляем пустые поля
         */
        foreach ($results as $key => $value) {
            if( $key === 'comment' ) {
                continue;
            }

            if( empty($value) ) {
                unset($results[$key]);
            }
        }

        return $results;
    }

    /**
     * Обробатываем результаты со страницы "Результаты"
     *
     * @param $event
     * @return array|array[]
     */
    public function process($event)
    {
        $context = null;
        $results = [];

        if( is_null($context) ) {
            $context = Factors::CONTEXT_MAIN;
        }

        $scoreString = $event['score'] ?? '';
        $comments = [];

        if( !empty($event['comment1']) ) {
            $comments[] = $event['comment1'];
        }

        if( !empty($event['comment2']) ) {
            $comments[] = $event['comment2'];
        }

        if( !empty($event['comment3']) ) {
            $comments[] = $event['comment3'];
        }

        $hasScore = preg_match('#^(\d+):(\d+)#isu', $scoreString, $generalScore);

        /**
         * Вытаскиваем общий счёт
         */
        if( $hasScore > 0 ) {
            $score = [
                'general' => [
                    'time'   => date('Y-m-d H:i:s'), // 2018-01-11 17:55:23
                    'first'  => (int) $generalScore[1],
                    'second' => (int) $generalScore[2]
                ],
                'periods' => []
            ];

            /**
             * Результат по периодам из маски
             */
            $hasPeriodsScore = preg_match('#\((.*)\)#isu', $scoreString, $matchPeriodsScore);

            if( $hasPeriodsScore > 0) {
                /**
                 * Выдёргиваем счёт по перодам
                 */
                preg_match_all('#(\d+)-(\d+)#isu', $matchPeriodsScore[1], $periodsScore, PREG_SET_ORDER);

                foreach ( $periodsScore as $periodScore ) {
                    $score['periods'][] = [
                        'time'   => date('Y-m-d H:i:s'), // 2018-01-11 17:55:23
                        'first'  => (int) $periodScore[1],
                        'second' => (int) $periodScore[2]
                    ];
                }
            }

            if($score['general']['first'] === 0 && $score['general']['second'] === 0) {
                $score = [];
            }
            
            $results[$context] = $score;
        }

        /**
         * Если основной контекст тогда проверяем наличие комментария
         */
        if( !empty($comments) && $context === Factors::CONTEXT_MAIN ) {
            $results['comment'] = implode(', ', $comments);
        }

        /**
         * Удаляем пустые поля
         */
        foreach ($results as $key => $value) {
            if( $key === 'comment' ) {
                continue;
            }

            if( empty($value) ) {
                unset($results[$key]);
            }
        }

        return $results;
    }

    public function  getVideo($event)
    {
        $comment = $event['comment']  ?? '';

        if( empty($comment) ) {
            return '';
        }

        $hasLink = preg_match('#(https?:\/\/.*?)(?=\s|$)#', $comment, $match);
        $link = $match[1] ?? '';

        if( !empty($hasLink) ) {
            $hasMatch = preg_match('#(twitch\.tv|youtube\.com)/(.+)$#', $link, $match);

            if( !empty($hasMatch) ) {
                if( $match[1] === 'youtube.com' ) {
                    preg_match('#v=(\w+)(?=&|$)#', $match[2], $channelMatch);
                    $link = "https://{$match[1]}/embed/{$channelMatch[1]}";
                }

                if( $match[1] === 'twitch.tv' ) {
                    $link = "https://player.{$match[1]}/?channel={$match[2]}";
                }

                return $link;
            } else {
                printf("[%s] Notice: Неизвестный источник трансляции : %s %s", date('d/M/Y H:i:s'), $link, PHP_EOL);
            }
        }

        return '';
    }

    /**
     * @param $results
     * @param $oldResults
     * @return mixed
     */
    private function _normalize($results, $oldResults)
    {
        /**
         * Проверяем не сбился ли основной счёт
         */
        if ( isset($results['score'], $oldResults['score']) ) {
            $previousScore = json_decode($oldResults['score'], true);

            $currentCommonScore  = $results['score']['general']['first']  + $results['score']['general']['second'];
            $previousCommonScore = $previousScore['general']['first'] + $previousScore['general']['second'];

            /**
             * Если счёт обнулился тогда забираем предыдущий
             */
            if( $currentCommonScore === 0 && $previousCommonScore > 1 ) {
                $results['score'] = $previousScore;
            }
        }

        return $results;
    }

    /**
     * Вытаскиваем счёт
     *
     * @param $event
     * @return array
     */
    private function _getScore($event) {

        if( !isset($event['score1']) || !isset($event['score2']) ) {
            return [];
        }

        $score = [
            'general' => [
                'time'   => date('Y-m-d H:i:s'), // 2018-01-11 17:55:23
                'first'  => (int) $event['score1'],
                'second' => (int) $event['score2']
            ],
            'periods' => []
        ];


        if( $event['comment'] !== '' ) {

            /**
             * Если это теннис "турнир из одного сета" тогда не вытаскиваем счёт по периодам
             */
            if( $event['sportId'] === 3 ) {
                $hasTurn = preg_match('#\*#isu', $event['comment']);

                if( $hasTurn > 0 ) {
                    return $score;
                }
            }

            /**
             * Выдёргиваем всё что в скобках
             */
            $hasPeriodsScore = preg_match('#\((.*)\)#isu', $event['comment'], $matchPeriodsScore);

            if( $hasPeriodsScore > 0) {
                /**
                 * Выдёргиваем счёт по перодам
                 */
                preg_match_all('#(\d+)\*?-(\d+)\*?#isu', $matchPeriodsScore[1], $periodsScore, PREG_SET_ORDER);

                foreach ($periodsScore as $periodScore) {
                    $score['periods'][] = [
                        'time'   => date('Y-m-d H:i:s'), // 2018-01-11 17:55:23
                        'first'  => (int) $periodScore[1],
                        'second' => (int) $periodScore[2]
                    ];
                }
            }

            /**
             * Если это не внутренние событие тогда выдёргиваем всё что вне скобак
             */
            if( array_search($event['context'], [Factors::CONTEXT_MAIN, Factors::CONTEXT_ADD_TIME, Factors::CONTEXT_OVERTIME]) !== false ) {
                $hasComment = preg_match('#(\(.*\))?([\w\d\s\-,:\+/]+)?#isu', $event['comment'], $matchComment);

                if( $hasComment > 0 ) {
                    $comment = $matchComment[2] ?? '';
                    $comment = trim(preg_replace('#https?.*#u', '', $comment));

                    self::$comment = $comment;
                } else {
                    printf("[%s] Notice: Немогу разобрать счёт: '%s' %s", date('d/M/Y H:i:s'), $event['comment'], PHP_EOL);
                }
            }
        }

        return $score;
    }

    /**
     * Получаем текущий период
     *
     * @param $sportId
     * @param $results
     * @return int
     */
    private function _getPeriod($sportId, $results) {
        $period = (isset($results['score']) ? count($results['score']['periods']) : 0);

        if( !empty($results['timer']) ) {
            $sportIds = [
                1,  // Футбол
                4,  // Хоккей
                10, // Гандбол
                13, // Регби
                14, // Футзал
                17, // Американский футбол
                19, // Хоккей с мячом
                24, // Хоккей на траве
                25, // Хоккей на роликах
                30, // Флорбол
                31, // Водное поло
                40, // Пляжный футбол
                44, // Австралийский футбол
                97, // Регби-лига
                98, // Регби-лига
            ];

            /**
             * Для некоторых спортов не даётся счёт для текущего периода
             */
            if( array_search($sportId, $sportIds) !== false ) {
                $period += 1;
            }
        }

        if( $period === 0 ) {
            $period = 1;
        }

        return $period;
    }

    /**
     * Вытаскиваем поинты
     *
     * @param $event
     * @return array
     */
    private function _getPoints($event) {

        $points = [];

        if( empty($event['comment']) ) {
            return $points;
        }

        /**
         * Указаны ли поинты например 15:30
         */
        $hasPoints = preg_match('#\((.*тайбрейк\s?)?(A|\d+)\*?-(A|\d+)\*?#isu', $event['comment'], $matchPoints);

        if( $hasPoints > 0 ) {
            $matchPoints[2] = ($matchPoints[2] === 'A' ? 'AVG' : $matchPoints[2]);
            $matchPoints[3] = ($matchPoints[3] === 'A' ? 'AVG' : $matchPoints[3]);

            $points = [
                'first'  => $matchPoints[2],
                'second' => $matchPoints[3],
                'period' => $event['period'],
                'time'   => date('Y-m-d H:i:s'), // 2018-01-11 17:55:23
            ];

            self::$comment = ($matchPoints[1] === '' ? self::$comment : $matchPoints[1]);

        }

        return $points;
    }

    /**
     * Вытаскиваем ИТОГ
     *
     * @param $event
     * @return int|string
     */
    private function _getItog($event) {
        $itog = '';

        if( empty($event['comment']) ) {
            return $itog;
        }

        /**
         * Есть ли ИТОГ
         */
        $hasItog = preg_match('#итог#isu', $event['comment']);

        if( $hasItog > 0 ) {
            self::$comment = 'Матч завершен';

            return 1;
        }

        return $itog;
    }

    /**
     * Вытаскиваем комментарий по событию
     *
     * @return string
     */
    private function _getComment() {
        $out = self::$comment;
        self::$comment = '';

        return $out;
    }

    /**
     * Собираем лог по геймам
     *
     * @param $sportSetting
     * @param $games
     * @param $results
     *
     * @return false|string
     */
    private function _generateGames($sportSetting, $games, $results)
    {
        $score  = $results['score'];
        $period = $results['period'];
        $points = $results['points'];
        $turn   = $results['turn'];

        $curPoint = $this->_calculatePoint($sportSetting, $score);
        $nextPoint = $curPoint + 1;

        $nextGame = $games[$nextPoint] ?? null;
        $curGame = $games[$curPoint] ?? null;

        // Если не существует гейм
        if( !isset($games[$nextPoint]) ) {
            $nextGame = [
                'team'   => 0,
                'game'   => $nextPoint,
                'period' => $period,
                'turn'   => $turn,
                'score'  => $score,
                'time'   => date('Y-m-d H:i:s'), // 2018-01-11 17:55:23
                'log'    => []
            ];
        }

        $pointHash = substr(md5($points['first'] . ':' . $points['second']),0, 6);

        // Если не существует поинта в рамках гейма
        if( !isset($nextGame['log'][$pointHash]) ) {
            $nextGame['log'][$pointHash] = $points;
            $nextGame['log'][$pointHash]['turn'] = $turn;
        }

        $nextGame['points'] = $points;

        /**
         * Рассчитываем предыдущий гейм
         */
        if( !is_null($curGame) ) {
            $games[$curPoint] = $curGame;
            $games[$curPoint]['team'] = $this->_calculateWinner($sportSetting, $nextGame['score'], $curGame['score']);
            $games[$curPoint]['period'] = $this->_calculatePeriod($curPoint, $results['score']);
        }

        if( !is_null($nextGame) ) {
            $games[$nextPoint] = $nextGame;
            $games[$nextPoint]['period'] = $this->_calculatePeriod($nextPoint, $results['score']);
        }

        return $games;
    }

    /**
     * Собираем лог результатов
     *
     * @param $sportSetting
     * @param $log
     * @param $results
     *
     * @return false|string
     */
   private function _generateLog($sportSetting, $log, $results)
   {
       foreach ( $results as $name => $content ) {
            if( array_search($name, self::$_resultIsScore) === false ) {
                continue;
            }

            $curPoint = $this->_calculatePoint($sportSetting, $content);
            $prevPoint = $curPoint - 1;

            // Не логируем нулевое очко
            if( $curPoint === 0 ) {
                continue;
            }

           if( !isset($log[$name]) ) {
               $log[$name] = [];
           }

           // Если были "отмены" тогда проверяем поинт + 1 и если лог существует тогда удаляем его
           if( isset($log[$name][$curPoint + 1]) ) {
               unset($log[$name][$curPoint + 1]);
           }

           $curLog = $log[$name][$curPoint] ?? null;
           $prevLog = $log[$name][$prevPoint] ?? null;

           // Если не существует поинт
           if( !isset($log[$name][$curPoint]) ) {
               $curLog = [
                    'team'   => 0,
                    'point'  => $curPoint,
                    'period' => $results['period'],
                    'timer'  => $results['timer'] ?? null,
                    'score'  => $content,
                    'time'   => date('Y-m-d H:i:s'), // 2018-01-11 17:55:23
                ];
           }

           if( !is_null($curLog) ) {
               $log[$name][$curPoint] = $curLog;
               $log[$name][$curPoint]['team'] = $this->_calculateWinner($sportSetting, $curLog['score'], $prevLog['score'] ?? null);
               $log[$name][$curPoint]['period'] = $this->_calculatePeriod($curPoint, $content);
           }
       }

       return $log;
   }

   private function _calculatePeriod($point, $result)
    {
        $limitMin = 1;
        $limitMax = 0;
        $period = 0;

        foreach ( $result['periods'] as $periodNum => $periodScore ) {
            $limitMax += $periodScore['first'] + $periodScore['second'];

            if( $point >= $limitMin && $point <= $limitMax ) {
                $period = $periodNum + 1;
                break;
            }

            $limitMin = $limitMax + 1;
        }

        return $period;
    }

   private function _calculatePoint($sportSetting, $result)
    {
        $point = $result['general']['first'] + $result['general']['second'];

        if( !empty($sportSetting['isTotalScore']) ) {
            $point = 0;

            foreach ( $result['periods'] as $periodNum => $periodScore ) {
                $point += $periodScore['first'] + $periodScore['second'];
            }
        }

        return $point;
    }

   private function _calculateWinner($sportSetting, $result, $oldResults)
   {
       $firstTeamOld = 0;
       $secondTeamOld = 0;

       if( !empty($oldResults) ) {
           $firstTeamOld = $oldResults['general']['first'];
           $secondTeamOld = $oldResults['general']['second'];

           if( !empty($sportSetting['isTotalScore']) ) {
               $firstTeamOld = 0;
               $secondTeamOld = 0;

               foreach ( $oldResults['periods'] as $periodNum => $periodScore ) {
                   $firstTeamOld += $periodScore['first'];
                   $secondTeamOld += $periodScore['second'];
               }
           }
       }

       $firstTeamNew = $result['general']['first'];
       $secondTeamNew = $result['general']['second'];

       if( !empty($sportSetting['isTotalScore']) ) {
           $firstTeamNew = 0;
           $secondTeamNew = 0;

           foreach ( $result['periods'] as $periodNum => $periodScore ) {
               $firstTeamNew += $periodScore['first'];
               $secondTeamNew += $periodScore['second'];
           }
       }

       $firstDiff = $firstTeamNew - $firstTeamOld;
       $secondDiff = $secondTeamNew - $secondTeamOld;

       if( $firstDiff === $secondDiff ) {
           return 0;
       }

       return $firstDiff > $secondDiff ? 1 : 2;
   }
}