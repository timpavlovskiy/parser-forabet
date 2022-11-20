<?php
$startTime = microtime(true);
printf("[%s] Info: Результаты \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/lib/Crud.php';
require_once dirname(__DIR__) . '/lib/Parser.php';
require_once dirname(__DIR__) . '/lib/Factors.php';
require_once dirname(__DIR__) . '/lib/Result.php';
require_once dirname(__DIR__) . '/lib/Helper.php';

$Crud = new Crud(getPDO(), getRedis());

try {
    $sportSettings = $Crud->getSportSettings();
} catch ( \Exception $e ) {
    printf("[%s] Error: %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
    exit(PHP_EOL);
}

try {
    $data = $Redis->get('Fonbet:Data:' . LIVE_CONTENT_NAME);
    $data = json_decode($data, true);

    if (PARSER_NEW_API_ENABLE) {
        $allFactors = [];
        foreach ($data['customFactors'] as $key => $customFactor) {
            $event = $Redis->get(CACHE_KEY_RAW_LIVE_EVENTS . ':' . $key);
            $allFactor = json_decode($event, true);
            if (empty($allFactor)) {
                continue;
            }
            if (is_array($allFactor) && !empty($allFactor['customFactors'])) {
                $allFactors = array_merge($allFactors, $allFactor['customFactors']);
            }
        }

        $data['customFactors'] = $allFactors;
    }

    if( !empty($data) ) {
        $data['sports'] = Helper::customSort($data['sports'] ?? []);
        $data['events'] = Helper::customSort($data['events'] ?? []);

        $data['sports']        = Helper::reindexByKey($data['sports'] ?? []);
        $data['events']        = Helper::reindexByKey($data['events'] ?? []);
        $data['eventMiscs']    = Helper::reindexByKey($data['eventMiscs'] ?? []);
        $data['eventBlocks']   = Helper::reindexByKey($data['eventBlocks'] ?? [], 'eventId');
        $data['announcements'] = Helper::reindexByKey($data['announcements'] ?? []);
        $data['customFactors'] = Helper::reindexFactors($data ?? []);
    }

} catch ( \Exception $e ) {
    printf("[%s] Error: %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
    exit(PHP_EOL);
}

if( empty($data) ) {
    printf("[%s] Error: Не пришли данные %s", date('d/M/Y H:i:s'), PHP_EOL);
    exit(PHP_EOL);
}

$ignoreSports  = $Crud->getIgnoreSports();
$sportMapper   = $Crud->getSportMapper('name');
$countryMapper = $Crud->getCountryMapper('name');

$Result = new Result();
$Parser = new Parser(
    $sportMapper,
    $ignoreSports,
    $countryMapper
);

try {
    $sportsFonbet = $Parser->parseLeagues(LIVE_CONTENT_NAME, $data);
    $eventsFonbet = $Parser->parseEvents($data, LIVE_CONTENT_NAME);
} catch ( \Exception $e ) {
    printf("[%s] Error: %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
    exit(PHP_EOL);
}

$localEvents  = $Crud->findEvents(1, PARSER_LIVE_ID, array_keys($eventsFonbet));
$localResults = $Crud->findResults(array_column($localEvents, 'id'));

$remoteResults = [];

foreach ( $eventsFonbet as $fbEventCode => $fbEvent ) {
    /*
     * Нормализуем событие
     */
    if ( !isset($fbEvent['comment']) ) {
        $fbEvent['comment'] = '';
    }

    if ( !isset($fbEvent['score1']) ) {
        $fbEvent['score1'] = 0;
    }

    if ( !isset($fbEvent['score2']) ) {
        $fbEvent['score2'] = 0;
    }

    /**
     * Если пришло событие которого нету в базе тогда не обрабатываем его
     */
    if ( !isset($localEvents[$fbEventCode]) ) {
        continue;
    }

    $localEvent = $localEvents[$fbEventCode];
    $localResult = $localResults[$localEvent['id']] ?? [];

    /**
     * Разбираем результаты
     */
    $sportSetting = $sportSettings[$fbEvent['sportId']];
    $results = $Result->decode($sportSetting, $fbEvent, $localResult);

    $remoteResults[$fbEventCode] = $results;
}

$Parser->processResults($localEvents, $remoteResults, $localResults);
$newResults = $Parser->getNewResults();
$updateResults = $Parser->getUpdateResults();

$dbTime = microtime(true);
printf("[%s] Info: Работа с БД \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

try {
    $Crud->updateResults($updateResults);
} catch ( \Exception $e ) {
    printf("[%s] Error: Во время обновления результатов произошла ошибка %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
    exit(PHP_EOL);
}

try {
    $Crud->addResults($newResults);
} catch ( \Exception $e ) {
    printf("[%s] Error: Во время добавления результатов произошла ошибка %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
    exit(PHP_EOL);
}

printf("[%s] Info: Работа с БД \t(%f seconds) %s", date('d/M/Y H:i:s'), microtime(true) - $dbTime, PHP_EOL);
printf("[%s] Результаты \t(финиш) \t(%f) %s", date('d/M/Y H:i:s'), microtime(true) - $startTime, PHP_EOL);
echo PHP_EOL;
