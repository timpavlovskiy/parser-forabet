<?php

class Crud
{
    /**
     * @var \PDO
     */
    private $_db;

    /**
     * @var \Redis
     */
    private $_redis;

    private $_ignoreSport = [
        'Лотереи' => true,
        'Политика' => true,
        'Культура' => true,
        'World of Tanks' => true,
        'Rocket League' => true,
        'Собачьи бега' => true,
        'Кунг-волейбол' => true,
        'Спорт симуляторы' => true,
        'Лошадиные скачки' => true,
    ];


    /**
     * Crud constructor.
     *
     * @param $db
     * @param $redis
     */
	public function __construct($db, $redis)
	{
        $this->_db = $db;
        $this->_redis = $redis;
	}

    /**
     * @return array
     */
	public function getSettings()
    {
        $out = [];

        $this->_db->query('SELECT `name`, `value` FROM `settings`')
            ->fetchAll(PDO::FETCH_FUNC, function($name, $value) use (&$out) {
                $out[$name] = (int) $value;
            });

        return $out;
    }

    /**
     * @return array
     */
    public function getIgnoreSports()
    {
        return $this->_ignoreSport;
    }

    /**
     * @param string $keyBy
     * @return array
     */
    public function getSportMapper($keyBy = 'id')
    {
        $out = [];

        $this->_db->query('SELECT `id`, `name`, `margin`, `marginLive`, `maximum`, `parser_id_live`, `parser_id_prematch` FROM sport')
            ->fetchAll(\PDO::FETCH_FUNC, function ($id, $name, $margin, $marginLive, $maximum, $parserLive, $parserPrematch) use ($keyBy, &$out) {
                $key = ($keyBy === 'name' ? $name : $id);

                $out[$key] = [
                    'id'             => (int) $id,
                    'name'           => $name,
                    'marginPrematch' => round($margin, 2),
                    'marginLive'     => round($marginLive, 2),
                    'maximum'        => round($maximum, 2),
                    'parserLive'     => (int) $parserLive,
                    'parserPrematch' => (int) $parserPrematch,
                ];
            });


        if( empty($out) ) {
            return $out;
        }

        /**
         * Добавляем алиасы для спортов
         */
        if( $keyBy === 'name' ) {
            $sportAliases = [
                'Dota-2'             => 'Dota 2',
                'Counter-Strike'     => 'CSGO',
                'StarCraft 2'        => 'SC2',
                'StarCraft'          => 'SC2',
                'LoL'                => 'League of Legends',
                'LOL'                => 'League of Legends',
                'Баскетбол 3x3'      => 'Баскетбол',
                'Единоборства'       => 'MMA',
                'Наст. теннис'       => 'Настольный теннис',
                'Жен. Киберволейбол' => 'Киберволейбол'
            ];

            foreach ( $sportAliases as $aliasName => $sportName ) {
                if( !isset($out[$sportName]) ) {
                    continue;
                }

                $out[$aliasName] = $out[$sportName];
            }

            foreach ( $out as $sportName => $sport ) {
                $cyberName = ucfirst('Кибер' . mb_strtolower($sportName));

                $out[$cyberName] = $out[$sportName];
                $out[$cyberName]['cyber'] = true;
            }
        }

        return $out;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getSportSettings()
    {
        $fileName = dirname(SITE_DIR) . '/common/settings/sport.json';

        if( file_exists($fileName) === false ) {
            throw new \Exception('Not found file for sport settings');
        }

        $data = file_get_contents($fileName);

        $out = json_decode($data, true);
        $out = $out ?? [];

        return $out;
    }

    /**
     * @param string $keyBy
     * @return array
     */
    public function getCountryMapper($keyBy = 'id')
    {
        $out = [];

        $this->_db->query('SELECT `id`, `name` FROM `country`')
            ->fetchAll(\PDO::FETCH_FUNC, function ($id, $name) use ($keyBy, &$out) {
                $key = ($keyBy === 'name' ? $name : $id);

                $out[$key] = [
                    'id'   => (int) $id,
                    'name' => $name,
                ];
            });

        return $out;
    }

    /**
     * @return array
     */
    public function getCategoryMapper()
    {
        $out = [];

        $this->_db->query('SELECT `id`, `name`, `margin_line`, `margin_live`, `maximum_line`, `maximum_live`, `reserve` FROM `league_category`')
            ->fetchAll(\PDO::FETCH_FUNC, function ($id, $name, $marginPrematch, $marginLive, $maximumPrematch, $maximumLive, $reserve) use (&$out) {
                $out[$id] = [
                    'id'              => (int) $id,
                    'name'            => $name,
                    'marginPrematch'  => round($marginPrematch, 2),
                    'marginLive'      => round($marginLive, 2),
                    'maximumPrematch' => round($maximumPrematch, 2),
                    'maximumLive'     => round($maximumLive, 2),
                    'reserve'         => round($reserve, 2)
                ];
            });

        return $out;
    }

    /**
     * @return array
     */
	public function getMarginConstraint()
    {
        $out = [];

        $this->_db
            ->query('SELECT `name`, `value` FROM `settings`')
            ->fetchAll(\PDO::FETCH_FUNC, function ($name, $value) use (&$out) {

                switch ( $name ) {
                    case 'MaximumMarginPrematch':
                    case 'MinimumMarginPrematch':
                        if ($name === 'MaximumMarginPrematch') {
                            $out['line']['max'] = (float) $value;
                        }

                        if ($name === 'MinimumMarginPrematch') {
                            $out['line']['min'] = (float) $value;
                        }

                        break;

                    case 'MaximumMarginLive':
                    case 'MinimumMarginLive':
                        if ($name === 'MaximumMarginLive') {
                            $out['live']['max'] = (float) $value;
                        }

                        if ($name === 'MinimumMarginLive') {
                            $out['live']['min'] = (float) $value;
                        }

                        break;

                    case 'MarginStep':
                        $out['step'] = (float) $value;
                        break;

                    case 'MarginMode':
                        $out['mode'] = (int) $value;
                        break;
                }
            });

        return $out;
    }

    /**
     * @param int $parserId
     * @param array $ids
     * @param string $keyBy
     * @return array
     */
    public function findLeagues($parserId, array $ids, $keyBy = 'remote_id')
    {
        if ( empty($ids) ) {
            return [];
        }

        $out = [];

        $ids = array_map(function($item) {
            return $this->_db->quote($item);
        }, $ids);

        $sql = sprintf('SELECT `id`, `sport`, `category_id`, `name`, `maximum`, `reserve`, `margin`, `marginLive`, `type_publish`, `remote_id`, `remote_hash` FROM `league` WHERE `parser_id` = %s AND `%s` IN (%s)', $parserId, $keyBy, implode(',', $ids));
        $this->_db->query($sql)
            ->fetchAll(PDO::FETCH_FUNC, function ($id, $sportId, $categoryId, $name, $maximum, $reserve, $marginPrematch, $marginLive, $typePublish, $remoteId, $remoteHash) use ($keyBy, &$out) {
                $key = ($keyBy === 'remote_hash' ? $remoteHash : $remoteId);

                $out[$key] = [
                    'id'             => (int) $id,
                    'sportId'        => (int) $sportId,
                    'categoryId'     => (int) $categoryId,
                    'maximum'        => round($maximum, 2),
                    'reserve'        => round($reserve, 2),
                    'marginPrematch' => round($marginPrematch, 2),
                    'marginLive'     => round($marginLive, 2),
                    'typePublish'    => (int) $typePublish,
                    'remoteId'       => (int) $remoteId,
                    'remoteHash'     => $remoteHash,
                    'name'           => $name
                ];
            });

        return $out;
    }

    /**
     * @param $leagues
     * @return bool
     */
    public function updateLeagues(array $leagues)
    {
        if ( empty($leagues) ) {
            return true;
        }

        $leagueByName = [];

        foreach ( $leagues as $leagueId => $values ) {
            foreach ( $values as $name => $content ) {
                $content = $this->_db->quote($content);
                $leagueByName[$name][$leagueId] = $content;
            }
        }

        foreach ( $leagueByName as $name => $leagues ) {
            $case = [];

            foreach ( $leagues as $leagueId => $content ) {
                $case[$leagueId] = "WHEN {$leagueId} THEN {$content}";
            }

            $sql = sprintf('UPDATE `league` SET `%s` = CASE `id` %s ELSE `%s` END WHERE `id` IN (%s)', $name, implode(PHP_EOL, $case), $name, implode(',', array_keys($case)));
            $this->_db->exec($sql);
        }

        return true;
    }

    /**
     * @param array $leagues
     * @return bool
     */
    public function addLeagues(array $leagues)
    {
        if( empty($leagues) ) {
            return true;
        }

        $settings = $this->getSettings();

        $insert = [];
        $autoEnable = 0;
//        $autoEnable = $settings['ParserAutoEnable'] ?? 0;

        if( $autoEnable === 0 ) {
            $autoEnable = $settings['EventParserAutoEnableLive'] ?? 0;
        }

        $checked = 0;
        $typePublish = 1;

        if( $autoEnable === 1 ) {
            $checked = 1;
            $typePublish = 2; // события данного турнира публикуем
        }

        $checked     = $this->_db->quote($checked);
        $typePublish = $this->_db->quote($typePublish);

        foreach ( $leagues as $values ) {
            $sportId     = $this->_db->quote($values['sportId']);
            $parserId    = $this->_db->quote($values['parserId']);
            $countryId   = $this->_db->quote($values['countryId']);
            $remoteId    = $this->_db->quote($values['idRemote']);
            $name        = $this->_db->quote($values['name']);
            $order       = $this->_db->quote($values['order']);
            $top         = $this->_db->quote($values['isTop']);
            $longtime    = $this->_db->quote($values['longtime']);
            $remoteHash  = $this->_db->quote($values['hashRemote']);

            $description = json_encode($values['description']);
            $description = $this->_db->quote($description);

            /**
             * Маржа и максимумы для турнира
             */
            $maximum    = $values['maximum'];
            $margin     = $values['margin'];
            $marginLive = $values['marginLive'];

            /**
             * Если не турнир присутствует в blackList
             */
            if( $values['isBlack'] ) {
                $checked     = $this->_db->quote(1);
                $typePublish = $this->_db->quote(3); // события данного турнира не публикуем
            }

            $maximum    = $this->_db->quote($maximum);
            $margin     = $this->_db->quote($margin);
            $marginLive = $this->_db->quote($marginLive);

            $insert[] = "({$sportId}, {$name}, {$maximum}, {$margin}, {$marginLive}, {$countryId}, {$description}, {$order}, {$top}, {$longtime}, {$checked}, {$typePublish}, {$parserId}, {$remoteId}, {$remoteHash})";
        }

        $sql = sprintf('INSERT INTO `league` (`sport`, `name`, `maximum`, `margin`, `marginLive`, `country_id`, `description`, `order`, `top`, `longtime`, `is_checked`, `type_publish`, `parser_id`, `remote_id`, `remote_hash`) VALUES %s', implode(',', $insert));

        printf("[%s] Info: Старт транзакции для добавления турниров %s", date('d/M/Y H:i:s'), PHP_EOL);
        $this->_db->beginTransaction();
        $result = $this->_db->exec($sql);

        if ( $result === false ) {
            $this->_db->rollBack();

            printf('[%s] Info: Откат транзакции %s', date('d/M/Y H:i:s'),PHP_EOL);
            return false;
        }

        $this->_db->commit();
        printf('[%s] Info: Комит транзакции %s', date('d/M/Y H:i:s'), PHP_EOL);
        return true;
    }

    /**
     * @param int $type
     * @param int $parserId
     * @param array $ids
     * @param string $keyBy
     * @return array
     */
    public function findEvents($type, $parserId, array $ids, $keyBy = 'remote_id')
    {
        if ( empty($ids) ) {
            return [];
        }

        $out = [];

        $ids = array_map(function($item) {
            return $this->_db->quote($item);
        }, $ids);

        $sql = sprintf('SELECT `id`, `sportId`, `league`, `first`, `second`,`type`, `status`, `time`, `margin`, `remote_id`, `remote_hash` FROM `event` WHERE `type` = %s AND `parser_id` = %s AND `%s` IN (%s)', $type, $parserId, $keyBy, implode(',', $ids));
        $this->_db->query($sql)
            ->fetchAll(PDO::FETCH_FUNC, function ($id, $sportId, $leagueId, $team1, $team2, $type, $status, $time, $margin, $remoteId, $remoteHash) use ($keyBy, &$out) {
                $key = ($keyBy === 'remote_hash' ? $remoteHash : $remoteId);

                $out[$key] = [
                    'id'         => (int) $id,
                    'sportId'    => (int) $sportId,
                    'leagueId'   => (int) $leagueId,
                    'remoteId'   => (int) $remoteId,
                    'team1'      => $team1,
                    'team2'      => $team2,
                    'type'       => (int) $type,
                    'status'     => (int) $status,
                    'time'       => $time,
                    'margin'     => round($margin, 2),
                    'remoteHash' => $remoteHash
                ];
            });

        return $out;
    }

    /**
     * @param $events
     * @return bool
     */
    public function updateEvents(array $events)
    {
        if ( empty($events) ) {
            return true;
        }

        $eventByName = [];

        foreach ( $events as $eventId => $values ) {
            foreach ( $values as $name => $content ) {
                $content = $this->_db->quote($content);
                $eventByName[$name][$eventId] = $content;
            }
        }

        foreach ( $eventByName as $name => $events ) {
            $case = [];

            foreach ( $events as $eventId => $content ) {
                $case[$eventId] = "WHEN {$eventId} THEN {$content}";
            }

            $sql = sprintf('UPDATE `event` SET `%s` = CASE `id` %s ELSE `%s` END WHERE `id` IN (%s)', $name, implode(PHP_EOL, $case), $name, implode(',', array_keys($case)));
            $this->_db->exec($sql);
        }

        return true;
    }

    /**
     * @param array $events
     * @return bool
     */
    public function addEvents(array $events)
    {
        if( empty($events) ) {
            return true;
        }

        $settings = $this->getSettings();

        $insert = [];
        $autoEnable = 0;
//        $autoEnable = $settings['ParserAutoEnable'] ?? 0;

        if( $autoEnable === 0 ) {
            $autoEnable = $settings['EventParserAutoEnableLive'] ?? 0;
        }

        $checked = 0;
        $enabled = 0;

        if( $autoEnable === 1 ) {
            $checked = 1;
            $enabled = 1;
        }

        $checked = $this->_db->quote($checked);
        $enabled = $this->_db->quote($enabled);

        foreach ( $events as $values ) {
            $sportId    = $this->_db->quote($values['sportId']);
            $leagueId   = $this->_db->quote($values['leagueId']);
            $parserId   = $this->_db->quote($values['parserId']);
            $remoteId   = $this->_db->quote($values['idRemote']);
            $type       = $this->_db->quote($values['type']);
            $status     = $this->_db->quote($values['status']);
            $team1      = $this->_db->quote($values['team1']);
            $team1Id    = $this->_db->quote($values['team1Id']);
            $team2      = $this->_db->quote($values['team2']);
            $team2Id    = $this->_db->quote($values['team2Id']);
            $date       = $this->_db->quote($values['date']);
            $order      = $this->_db->quote($values['order']);
            $top        = $this->_db->quote($values['isTop']);
            $longtime   = $this->_db->quote($values['longtime']);
            $remoteHash = $this->_db->quote($values['hashRemote']);

            $description = json_encode([
                'statistic' => $values['statistic'],
                'tracker' => $values['tracker']
            ]);

            $description = $this->_db->quote($description);

            /**
             * Маржа и максимумы и резерв для события
             */
            $margin  = $this->_db->quote($values['margin']);
            $maximum = $this->_db->quote($values['maximum']);
            $reserve = $this->_db->quote($values['reserve']);

            if( $values['typePublish'] > 1 ) {
                $checked = $this->_db->quote(1);

                if( $values['typePublish'] === 2 ) {
                    $enabled = $this->_db->quote(1);
                }
            }

            /**
             * Если не турнир присутствует в blackList
             */
            if( $values['isBlack'] ) {
                $enabled = $this->_db->quote(0);
            }

            $insert[] = "({$sportId}, {$leagueId}, {$type}, {$status}, {$date}, {$team1}, {$team1Id}, {$team2}, {$team2Id}, {$description}, {$margin}, {$maximum}, {$reserve}, {$order}, {$top}, {$longtime}, {$enabled}, {$checked}, {$parserId}, {$remoteId}, {$remoteHash})";
        }

        $sql = sprintf('INSERT INTO `event` (`sportId`, `league`, `type`, `status`, `time`, `first`, `team1_id`, `second`, `team2_id`, `description`, `margin`, `maximum`, `reserve`, `order`, `top`, `longtime`, `enabled`, `is_checked`, `parser_id`, `remote_id`, `remote_hash`) VALUES %s', implode(',', $insert));

        printf("[%s] Info: Старт транзакции для добавления событий %s", date('d/M/Y H:i:s'), PHP_EOL);
        $this->_db->beginTransaction();
        $result = $this->_db->exec($sql);

        if ( $result === false ) {
            $this->_db->rollBack();
            printf('[%s] Info: Откат транзакции %s', date('d/M/Y H:i:s'),PHP_EOL);

            return false;
        }

        $this->_db->commit();
        printf('[%s] Info: Комит транзакции %s', date('d/M/Y H:i:s'), PHP_EOL);

        return true;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function findTeams(array $ids)
    {
        if ( empty($ids) ) {
            return [];
        }

        $out = [];

        $ids = array_map(function($item) {
            return $this->_db->quote($item);
        }, $ids);

        $sql = sprintf('SELECT `id`, `team_id`, `sport_id`, `name`, `disabled`, `remote_hash` FROM `team_alias` WHERE `origin` = 2 AND `remote_hash` IN (%s)', implode(',', $ids));
        $this->_db->query($sql)
            ->fetchAll(PDO::FETCH_FUNC, function ($id, $teamId, $sportId, $name, $disabled, $remoteHash) use (&$out) {
                $out[$remoteHash] = [
                    'id'         => (int) $id,
                    'teamId'     => (int) $teamId,
                    'sportId'    => (int) $sportId,
                    'name'       => $name,
                    'disabled'   => (int) $disabled,
                    'remoteHash' => $remoteHash
                ];
            });

        return $out;
    }

    /**
     * @param array $teams
     * @return bool
     */
    public function updateTeams(array $teams)
    {
        if ( empty($teams) ) {
            return true;
        }

        $teamsByName = [];

        foreach ( $teams as $eventId => $values ) {
            foreach ( $values as $name => $content ) {
                $content = $this->_db->quote($content);
                $teamsByName[$name][$eventId] = $content;
            }
        }

        foreach ( $teamsByName as $name => $events ) {
            $case = [];

            foreach ( $events as $eventId => $content ) {
                $case[$eventId] = "WHEN {$eventId} THEN {$content}";
            }

            $sql = sprintf('UPDATE `team_alias` SET `%s` = CASE `id` %s ELSE `%s` END WHERE `id` IN (%s)', $name, implode(PHP_EOL, $case), $name, implode(',', array_keys($case)));
            $this->_db->exec($sql);
        }

        return true;
    }

    /**
     * @param array $teams
     * @return bool
     */
    public function addTeams(array $teams)
    {
        if( empty($teams) ) {
            return true;
        }

         $insert = [];

        $teamId   = $this->_db->quote(0);
        $origin   = $this->_db->quote(2);
        $disabled = $this->_db->quote(0);

        foreach ( $teams as $values ) {
            $sportId    = $this->_db->quote($values['sportId']);
            $name       = $this->_db->quote($values['name']);
            $leagueName = $this->_db->quote($values['leagueName']);
            $remoteHash = $this->_db->quote($values['hashRemote']);

            $insert[] = "({$teamId}, {$sportId}, {$name}, {$origin}, {$disabled}, {$leagueName}, {$remoteHash})";
        }

        $sql = sprintf('INSERT INTO `team_alias` (`team_id`, `sport_id`, `name`, `origin`, `disabled`, `league`, `remote_hash`) VALUES %s', implode(',', $insert));

        printf("[%s] Info: Старт транзакции для добавления команд %s", date('d/M/Y H:i:s'), PHP_EOL);
        $this->_db->beginTransaction();
        $result = $this->_db->exec($sql);

        if ( $result === false ) {
            $this->_db->rollBack();

            printf('[%s] Info: Откат транзакции %s', date('d/M/Y H:i:s'),PHP_EOL);
            return false;
        }

        $this->_db->commit();
        printf('[%s] Info: Комит транзакции %s', date('d/M/Y H:i:s'), PHP_EOL);
        return true;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function findResults(array $ids)
    {
        if ( empty($ids) ) {
            return [];
        }

        $out = [];

        $ids = array_map(function($item) {
            return $this->_db->quote($item);
        }, $ids);

        $sql = sprintf('SELECT `event`, `name`, `content` FROM `result` WHERE `event` IN (%s)', implode(',', $ids));
        $this->_db->query($sql)
            ->fetchAll(PDO::FETCH_FUNC, function ($eventId, $name, $content) use (&$out) {
                if( !isset($out[$eventId]) ) {
                    $out[$eventId] = [];
                }

                $out[$eventId][$name] = $content;
            });

        return $out;
    }

    /**
     * @param array $results
     * @return bool
     */
    public function updateResults(array $results)
    {
        if ( empty($results) ) {
            return true;
        }

        $resultsByName = [];

        foreach ( $results as $eventId => $values ) {
            foreach ( $values as $name => $content ) {
                /**
                 * Массивы приводм к строке
                 */
                if( is_array($content) ) {
                    $content = json_encode($content);
                }

                $content = $this->_db->quote($content);
                $resultsByName[$name][$eventId] = $content;
            }
        }

        foreach ( $resultsByName as $name => $events ) {
            $name = $this->_db->quote($name);
            $case = [];

            foreach ( $events as $eventId => $content ) {
                $case[$eventId] = "WHEN {$eventId} THEN {$content}";
            }

            $sql = sprintf('UPDATE `result` SET `content` = CASE `event` %s ELSE `content` END WHERE `name` = %s AND `event` IN (%s)', implode(PHP_EOL, $case), $name, implode(',', array_keys($case)));
            $this->_db->exec($sql);
        }

        return true;
    }

    /**
     * @param array $results
     * @return bool
     */
    public function addResults(array $results)
    {
        if ( empty($results) ) {
            return true;
        }

        $insert = [];

        foreach ($results as $eventId => $values) {
            foreach ($values as $name => $content) {
                /**
                 * Массивы приводм к строке
                 */
                if( is_array($content) ) {
                    $content = json_encode($content);
                }

                $name = $this->_db->quote($name);
                $content = $this->_db->quote($content);

                $insert[] = "({$eventId}, {$name}, {$content})";
            }
        }

        $sql = sprintf('INSERT INTO `result` (`event`, `name`, `content`) VALUES %s', implode(',', $insert));

        printf('[%s] Info: Старт транзакции для добавления результатов%s', date('d/M/Y H:i:s'), PHP_EOL);
        $this->_db->beginTransaction();
        $result = $this->_db->exec($sql);

        if ( $result === false ) {
            $this->_db->rollBack();

            printf('[%s] Info: Откат транзакции %s', date('d/M/Y H:i:s'),PHP_EOL);
            return false;
        }

        $this->_db->commit();

        printf('[%s] Info: Комит транзакции %s', date('d/M/Y H:i:s'), PHP_EOL);
        return true;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function findVideos(array $ids)
    {
        if ( empty($ids) ) {
            return [];
        }

        $out = [];

        $ids = array_map(function($item) {
            return $this->_db->quote($item);
        }, $ids);

        $sourceNumber = $this->_db->quote(4);

        $sql = sprintf('SELECT `id`, `event_id`, `remote_id`, `url` FROM `event_video` WHERE `source_number` = %s AND `event_id` IN (%s)', $sourceNumber, implode(',', $ids));
        $this->_db->query($sql)
            ->fetchAll(PDO::FETCH_FUNC, function ($id, $eventId, $remoteId, $url) use (&$out) {
                $out[$eventId] = [
                    'id'       => (int) $id,
                    'eventId'  => (int) $eventId,
                    'remoteId' => (int) $remoteId,
                    'url'      => $url
                ];
            });

        return $out;
    }

    /**
     * @param array $videos
     * @return bool
     */
    public function updateVideos(array $videos)
    {
        if ( empty($videos) ) {
            return true;
        }

        $videosByName = [];

        foreach ( $videos as $videoId => $values ) {
            foreach ( $values as $name => $content ) {
                $content = $this->_db->quote($content);
                $videosByName[$name][$videoId] = $content;
            }
        }

        foreach ( $videosByName as $name => $videos ) {
            $case = [];

            foreach ( $videos as $videoId => $content ) {
                $case[$videoId] = "WHEN {$videoId} THEN {$content}";
            }

            $sql = sprintf('UPDATE `event_video` SET `%s` = CASE `id` %s ELSE `%s` END WHERE `id` IN (%s)', $name, implode(PHP_EOL, $case), $name, implode(',', array_keys($case)));
            $this->_db->exec($sql);
        }

        return true;
    }

    /**
     * @param array $videos
     * @return bool
     */
    public function addVideos(array $videos)
    {
        if ( empty($videos) ) {
            return true;
        }

        $insert = [];

        $type         = $this->_db->quote('embed');
        $sourceNumber = $this->_db->quote(4);

        foreach ( $videos as $values ) {
            $eventId  = $this->_db->quote($values['eventId']);
            $sportId  = $this->_db->quote($values['sportId']);
            $remoteId = $this->_db->quote($values['idRemote']);
            $url      = $this->_db->quote($values['url']);
            $team1    = $this->_db->quote($values['team1']);
            $team2    = $this->_db->quote($values['team1']);

            $insert[] = "({$eventId}, {$sportId}, {$remoteId}, {$sourceNumber}, {$type}, {$url}, {$team1}, {$team2})";
        }

        $sql = sprintf('INSERT INTO `event_video` (`event_id`, `sport_id`, `remote_id`, `source_number`, `type`, `url`, `team1`, `team2`) VALUES %s', implode(',', $insert));

        printf("[%s] Info: Старт транзакции для добавления видеотрансляций %s", date('d/M/Y H:i:s'), PHP_EOL);
        $this->_db->beginTransaction();
        $result = $this->_db->exec($sql);

        if ( $result === false ) {
            $this->_db->rollBack();

            printf('[%s] Info: Откат транзакции %s', date('d/M/Y H:i:s'),PHP_EOL);
            return false;
        }

        $this->_db->commit();
        printf('[%s] Info: Комит транзакции %s', date('d/M/Y H:i:s'), PHP_EOL);
        return true;
    }

    /**
     * @param int $type
     * @param int $parserId
     * @param array $statuses
     * @param array $finished
     * @return bool
     */
    public function updateMultipleStatusAndDisableOld($type, $parserId, array $statuses, array $finished)
    {
        $status = $this->_db->quote(0);
        $sqlUpdateOld = '';

        if ( !empty($statuses) ) {
            $case = [];

            foreach ( $statuses as $eventId => $values ) {
                if( $values['new'] !== $values['old'] ) {
                    $value = $this->_db->quote($values['new']);
                    $case[$eventId] = "WHEN {$eventId} THEN {$value}";
                }
            }

            if( !empty($case) ) {
                $sql = sprintf('UPDATE `event` SET `status` = CASE `id` %s ELSE `status` END WHERE `type` = %s AND `parser_id` = %s AND `id` IN (%s)', implode(PHP_EOL, $case), $type, $parserId, implode(',', array_keys($case)));
                $this->_db->exec($sql);
            }

//            $sqlUpdateOld = sprintf('UPDATE `event` SET `status` = %s WHERE `type` = %s AND `parser_id` = %s AND `time` BETWEEN ADDDATE(NOW(), INTERVAL -24 HOUR) AND NOW() AND `id` NOT IN (%s)', $status, $type, $parserId, implode(',', array_keys($statuses)));
        }

        if( !empty($finished) ) {
            $sqlUpdateOld = sprintf('UPDATE `event` SET `status` = %s WHERE `type` = %s AND `parser_id` = %s AND `id` IN (%s)', $status, $type, $parserId, implode(',', array_keys($finished)));
        }

        if( !empty($sqlUpdateOld) ) {
            $this->_db->exec($sqlUpdateOld);
        }

        return true;
    }

    /**
     * @return array
     */
    public function findMarkets(): array
    {
        $out = [];

        $this->_db
            ->query('SELECT `id`, `name`, `margin`, `is_addition` FROM `market`')
            ->fetchAll(\PDO::FETCH_FUNC,
                function ($id, $name, $margin, $is_addition) use (&$out) {
                    $out[$id] = [
                        'id'          => (int) $id,
                        'name'        => $name,
                        'margin'      => round($margin, 2),
                        'is_addition' => (bool) (int) $is_addition,
                    ];
                });

        return $out;
    }

    /**
     * @param array $id
     * @return array
     */
    public function findMarketMargin(array $id): array
    {
        $out = [];

        if ( empty($id) ) {
            return [];
        }

        $idStr = implode(',', $id);
        $sql = sprintf('SELECT `event_id`, `market_id`, `margin`, `is_addition`, `is_disabled` FROM `market_margin` WHERE `event_id` IN (%s)', $idStr);

        $this->_db->query($sql)
            ->fetchAll(\PDO::FETCH_FUNC, function ($id, $name, $margin, $is_addition, $is_disabled) use (&$out) {
                $out[$id][$name] = [
                    'id'          => (int) $id,
                    'name'        => (int) $name,
                    'margin'      => round($margin, 2),
                    'is_addition' => (bool) (int) $is_addition,
                    'is_disabled' => (bool) (int) $is_disabled,
                ];
            });

        return $out;
    }

    /**
     * @return array
     */
    public function getBlackList()
    {
        $out = [];

        $this->_db
            ->query('SELECT `id`, `sport_id`, `value`, `margin`, `marginLive`, `maximum`, `reserve` from `event_blacklist`')
            ->fetchAll(\PDO::FETCH_FUNC, function ($id, $sportId, $value, $marginPrematch, $marginLive, $maximum, $reserve) use (&$out) {
                $out[] = [
                    'id'             => (int) $id,
                    'sportId'        => (int) $sportId,
                    'value'          => $value,
                    'marginPrematch' => round($marginPrematch, 2),
                    'marginLive'     => round($marginLive, 2),
                    'maximum'        => round($maximum, 2),
                    'reserve'        => round($reserve, 2)
                ];
            });

        return $out;
    }

    /**
     * @return array
     */
    public function getWhiteList()
    {
        $out = [];

        $this->_db
            ->query('SELECT `id`, `sport_id`, `value`, `margin`, `marginLive`, `maximum`, `reserve` from `event_whitelist`')
            ->fetchAll(\PDO::FETCH_FUNC, function ($id, $sportId, $value, $marginPrematch, $marginLive, $maximum, $reserve) use (&$out) {
                $out[] = [
                    'id'             => (int) $id,
                    'sportId'        => (int) $sportId,
                    'value'          => $value,
                    'marginPrematch' => round($marginPrematch, 2),
                    'marginLive'     => round($marginLive, 2),
                    'maximum'        => round($maximum, 2),
                    'reserve'        => round($reserve, 2)
                ];
            });

        return $out;
    }

    /**
     * @param int $type
     *
     * @return array
     */
    public function getNeedToRunEvents($type = 1): array
    {
        if (!in_array($type, [1, 0], true)) {
            throw new \RuntimeException('Error: $type may 0 or 1 value');
        }

        $out = [];

        if ($type === 0) {
            $parserId = PARSER_LINE_ID;
        } else if (
            $type === 1) {
            $parserId = PARSER_LIVE_ID;
        }

        $query = "SELECT event.remote_id 
                  FROM event 
                  WHERE event.type = {$type} 
                    AND (event.status = 1 OR event.status = 2)
                    AND event.enabled = 1
                    AND event.parser_id = {$parserId}";

        $this->_db->query($query)
            ->fetchAll(PDO::FETCH_FUNC, static function ($eventId) use (&$out) {
                $out[$eventId] = $eventId;
            });

        return $out;
    }
}