<?php
$startTime = microtime(true);
printf("[%s] Info: Парсер \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/Crud.php';
require_once __DIR__ . '/lib/Helper.php';
require_once __DIR__ . '/lib/Parser.php';
require_once __DIR__ . '/lib/Factors.php';
require_once __DIR__ . '/lib/Country.php';
require_once __DIR__ . '/lib/File.php';

$data = [
    LIVE_CONTENT_NAME => []
];

$Crud = new Crud(getPDO(), getRedis());
$File = new File();

$data[LINE_CONTENT_NAME] = $File->load(__DIR__ . '/data/' . LINE_CONTENT_NAME . '.json', LINE_DATA_TTL);
$data[LIVE_CONTENT_NAME] = $File->load(__DIR__ . '/data/' . LIVE_CONTENT_NAME . '.json', LIVE_DATA_TTL);
$data[LIVE_CONTENT_NAME] = is_array($data[LIVE_CONTENT_NAME]) ? $data[LIVE_CONTENT_NAME] : [];

if( empty($data[LIVE_CONTENT_NAME]) || empty($data[LINE_CONTENT_NAME])) {
    printf("[%s] Error: Не пришли данные %s", date('d/M/Y H:i:s'), PHP_EOL);
    exit(PHP_EOL);
}

$ignoreSports   = $Crud->getIgnoreSports();
$sportMapper    = $Crud->getSportMapper('name');
$categoryMapper = $Crud->getCategoryMapper();
$countryMapper  = $Crud->getCountryMapper('name');

Helper::setBlackList($Crud->getBlackList());
Helper::setWhiteList($Crud->getWhiteList());

foreach ($data as $typeStr => $content) {
    if( empty($content) ) {
        continue;
    }

    $contentTime = microtime(true);

    switch ($typeStr) {
        case LIVE_CONTENT_NAME:
            $parserId = PARSER_LIVE_ID;
            $type = 1;
            break;
        case LINE_CONTENT_NAME:
            $parserId = PARSER_LINE_ID;
            $type = 0;
            break;
        default:
            continue 2;
    }

    printf("[%s] Info: Обработка %s контента %s", date('d/M/Y H:i:s'), $typeStr, PHP_EOL);

    $Parser = new Parser(
        $sportMapper,
        $ignoreSports,
        $countryMapper
    );

    $allLeagues = [];
    $allEvents = [];

    try {
        $allLeagues[$typeStr] = $Parser->parseLeagues($typeStr, $content);
        $allEvents[$typeStr] = $Parser->parseEvents($content, $typeStr);

        if( $typeStr === LIVE_CONTENT_NAME ) {
            $allLeagues['anons'] = $Parser->parserLeaguesAnons($typeStr, $content);
            $allEvents['anons'] = $Parser->parseEventsAnons($content);
        }
    } catch ( \Exception $e ) {
        printf("[%s] Error: %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
        exit(PHP_EOL);
    }

    $remoteLeagues = [];
    $remoteEvents  = [];
    $remoteTeams   = [];

    foreach ( $allLeagues as $leagues ) {
        foreach ( $leagues as $remoteId => $league ) {
            /**
             * Получаем настройки для текущего турнира
             */
            $sport = $sportMapper[$league['sportName']];

            $maximum        = $sport['maximum'];
            $marginLive     = $sport['marginLive'];
            $marginPrematch = $sport['marginPrematch'];

            /**
             * На основе названия турнира вытаскиваем страну
             */
            $countryId = $Parser->getCountryId($league['leagueName']);

            if( $countryId === 1 ) {
                $countryId = Country::getLocalCountyId($league);
            }

            /**
             * Забираем настройки из blacklist
             */
            $blackListItem = Helper::hasInBlackList($league['sportId'], $league['leagueName']);
            $isBlack = (bool) $blackListItem;

            if( !empty($blackListItem) ) {
                $maximum        = $blackListItem['maximum'];
                $marginLive     = $blackListItem['marginLive'];
                $marginPrematch = $blackListItem['marginPrematch'];
            }

            $remoteLeagues[$remoteId] = [
                'id'         => null,
                'sportId'    => $league['sportId'],
                'countryId'  => $countryId,
                'parserId'   => $parserId,
                'idRemote'   => $league['remoteId'],
                'name'       => $league['leagueName'],
                'order'      => $league['order'],
                'isTop'      => 0,
                'longtime'   => 0,
                'isBlack'    => $isBlack,
                'maximum'    => $maximum,
                'margin'     => $marginPrematch,
                'marginLive' => $marginLive,
                'description'=> [],
                'hashRemote' => Helper::leagueHash($league['sportId'], $league['leagueName'])
            ];
        }
    }

    foreach ( $allEvents as $events ) {
        foreach ( $events as $remoteId => $event ) {
            $remoteLeague = $remoteLeagues[$event['leagueId']];

            $idRemoteLeague   = $remoteLeague['idRemote'];
            $hashRemoteLeague = $remoteLeague['hashRemote'];

            $team1 = $event['team1'];
            $team2 = $event['team2'] ?? '';

            $team = [
                'sportId' => $event['sportId'],
                'leagueName' => $remoteLeague['name'],
            ];

            if( !empty($team1) ) {
                $remoteTeams[] = array_merge($team, [
                    'name' => $team1,
                    'hashRemote' => Helper::getTeamHash($event['sportId'], $team1)
                ]);
            }

            if( !empty($team2) ) {
                $remoteTeams[] = array_merge($team, [
                    'name' => $team2,
                    'hashRemote' => Helper::getTeamHash($event['sportId'], $team2)
                ]);
            }

            $date = date('Y-m-d H:i:00', $event['startTime']);
            $longtime = empty($team2) ? 1 : 0;
            $isTop = 0;

            if( isset($event['state'], $event['state']['inHotList']) ) {
                $isTop = (int) $event['state']['inHotList'];
            }

            $remoteLeagues[$idRemoteLeague]['isTop'] = $isTop;
            $remoteLeagues[$idRemoteLeague]['longtime'] = $longtime;

            $remoteEvents[$remoteId] = [
                'id'               => null,
                'sportId'          => $event['sportId'],
                'leagueId'         => null,
                'parserId'         => $parserId,
                'idRemote'         => $event['id'],
                'idRemoteLeague'   => $idRemoteLeague,
                'hashRemoteLeague' => $hashRemoteLeague,
                'type'             => $type,
                'status'           => 0,
                'team1'            => $team1,
                'team1Id'          => 0,
                'team2'            => $team2,
                'team2Id'          => 0,
                'date'             => $date,
                'order'            => $event['order'] ?? 0,
                'isTop'            => $isTop,
                'isBlack'          => false,
                'longtime'         => $longtime,
                'statistic'        => null,
                'tracker'          => $event['id'] ?? null,
                'hashRemote'       => Helper::eventHash($event['sportId'], $date, $team1, $team2)
            ];
        }
    }

    /**
     * Работаем с командами
     */
    $teamTime = microtime(true);
    printf("[%s] Info: Работа с командами \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

    $teamHashes = array_column($remoteTeams, 'hashRemote');
    $teamsByHashes = $Crud->findTeams($teamHashes);

    $Parser->processTeams($remoteTeams, $teamsByHashes);
    $newTeams = $Parser->getNewTeams();
    $updateTeams = $Parser->getUpdateTeams();

    try {
        $Crud->updateTeams($updateTeams);
    } catch ( \Exception $e ) {
        printf("[%s] Error: Во время обновления команд произошла ошибка %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
        exit(PHP_EOL);
    }

    try {
        $Crud->addTeams($newTeams);
    } catch ( \Exception $e ) {
        printf("[%s] Error: Во время добавления команд произошла ошибка %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
        exit(PHP_EOL);
    }

    printf("[%s] Info: Работа с командами \t(%f seconds) %s", date('d/M/Y H:i:s'), microtime(true) - $teamTime, PHP_EOL);

    /**
     * Работаем с турнирами
     */
    $leagueTime = microtime(true);
    printf("[%s] Info: Работа с турнирами \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

    $leagueIds = array_column($remoteLeagues, 'idRemote');
    $leaguesByIds = $Crud->findLeagues($parserId, $leagueIds, 'remote_id');

    $leagueHashes = array_column($remoteLeagues, 'hashRemote');
    $leaguesByHashes = $Crud->findLeagues($parserId, $leagueHashes, 'remote_hash');

    $Parser->processLeagues($remoteLeagues, $leaguesByIds, $leaguesByHashes);
    $newLeagues = $Parser->getNewLeagues();
    $updateLeagues = $Parser->getUpdateLeagues();

    try {
        $Crud->updateLeagues($updateLeagues);
    } catch ( \Exception $e ) {
        printf("[%s] Error: Во время обновления турниров произошла ошибка %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
        exit(PHP_EOL);
    }

    try {
        $Crud->addLeagues($newLeagues);
    } catch ( \Exception $e ) {
        printf("[%s] Error: Во время добавления турниров произошла ошибка %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
        exit(PHP_EOL);
    }

    printf("[%s] Info: Работа с турнирами \t(%f seconds) %s", date('d/M/Y H:i:s'), microtime(true) - $leagueTime, PHP_EOL);

    /**
     * Работаем с собыиями
     */
    $eventsTime = microtime(true);
    printf("[%s] Info: Работа с событиями \t(start) %s", date('d/M/Y H:i:s'), PHP_EOL);

    $localLeagues = $Crud->findLeagues($parserId, $leagueIds, 'remote_id');
    $localTeams = $Crud->findTeams($teamHashes);

    $eventIds = array_column($remoteEvents, 'idRemote');
    $eventsByIds = $Crud->findEvents($type, $parserId, $eventIds, 'remote_id');

    $eventHashes = array_column($remoteEvents, 'hashRemote');
    $eventsByHashes = $Crud->findEvents($type, $parserId, $eventHashes, 'remote_hash');

    $Parser->processEvents($remoteEvents, $eventsByIds, $eventsByHashes);
    $newEvents = $Parser->getNewEvents();
    $updateEvents = $Parser->getUpdateEvents();

    foreach ( $newEvents as $remoteId => $event ) {
        if ( !isset($localLeagues[$event['idRemoteLeague']]) ) {
            unset($newEvents[$remoteId]);
            continue;
        }

        $localLeague = $localLeagues[$event['idRemoteLeague']];

        $maximum = $localLeague['maximum'];
        $reserve = $localLeague['reserve'];

        $margin = ($type === 1 ? $localLeague['marginLive'] : $localLeague['marginPrematch']);

        if( $localLeague['categoryId'] !== 0 ) {
            $category = $categoryMapper[$localLeague['categoryId']];

            $maximum = ($type === 1 ? $category['maximumLive'] : $category['maximumPrematch']);
            $margin  = ($type === 1 ? $category['marginLive'] : $category['marginPrematch']);
            $reserve = $category['reserve'];
        }

        /**
         * Забираем настройки из blacklist
         */
        $blackListItem = Helper::hasInBlackList($localLeague['sportId'], $localLeague['name']);
        $isBlack = (bool) $blackListItem;

        if( !empty($blackListItem) ) {
            $maximum = $blackListItem['maximum'];
            $reserve = $blackListItem['reserve'];
            $margin  = ($type === 1 ? $blackListItem['marginLive'] : $blackListItem['marginPrematch']);
        }

        if( !empty($event['team1']) ) {
            $team1Hash = Helper::getTeamHash($event['sportId'], $event['team1']);
            $newEvents[$remoteId]['team1Id'] = $localTeams[$team1Hash]['teamId'];
        }

        if( !empty($event['team2']) ) {
            $team2Hash = Helper::getTeamHash($event['sportId'], $event['team2']);
            $newEvents[$remoteId]['team2Id'] = $localTeams[$team2Hash]['teamId'];
        }

        $newEvents[$remoteId]['margin']  = $margin;
        $newEvents[$remoteId]['maximum'] = $maximum;
        $newEvents[$remoteId]['reserve'] = $reserve;

        $newEvents[$remoteId]['leagueId'] = $localLeague['id'];
        $newEvents[$remoteId]['isBlack']  = $isBlack;
        $newEvents[$remoteId]['typePublish'] = $localLeague['typePublish'];
    }

    try {
        $Crud->updateEvents($updateEvents);
    } catch ( \Exception $e ) {
        printf("[%s] Error: Во время обновления событий произошла ошибка %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
        exit(PHP_EOL);
    }

    try {
        $Crud->addEvents($newEvents);
    } catch ( \Exception $e ) {
        printf("[%s] Error: Во время добавления событий произошла ошибка %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
        exit(PHP_EOL);
    }

    printf("[%s] Info: Работа с событиями \t(%f seconds) %s", date('d/M/Y H:i:s'), microtime(true) - $eventsTime, PHP_EOL);
    printf("[%s] Info: Обработка %s контента \t(%f seconds) %s", date('d/M/Y H:i:s'), $typeStr, microtime(true) - $contentTime,  PHP_EOL);
}

printf("[%s] Info: Парсер \t(%f seconds) %s", date('d/M/Y H:i:s'), microtime(true) - $startTime, PHP_EOL);
echo PHP_EOL;
