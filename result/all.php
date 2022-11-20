<?php
$startTime = microtime(true);
printf("[%s] Info: Результаты \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/lib/Curl.php';
require_once dirname(__DIR__) . '/lib/Crud.php';
require_once dirname(__DIR__) . '/lib/Parser.php';
require_once dirname(__DIR__) . '/lib/Factors.php';
require_once dirname(__DIR__) . '/lib/Result.php';
require_once dirname(__DIR__) . '/lib/Helper.php';

$date = $argv[1] ?? null;
$data = [];

$Crud = new Crud(getPDO(), getRedis());
$Curl = new Curl();

try {
    $sportSettings = $Crud->getSportSettings();
} catch ( \Exception $e ) {
    printf("[%s] Error: %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
    exit(PHP_EOL);
}

try {
    $data = $Curl->loadResults($date);

    if( !empty($data) ) {
        $data['sports']   = reindexByKey($data['sports'] ?? []);
        $data['sections'] = reindexByKey($data['sections'] ?? []);
        $data['events']   = reindexByKey($data['events'] ?? []);
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

foreach ([LIVE_CONTENT_NAME] as $typeStr) {
    $contentTime = microtime(true);

    switch ($typeStr) {
        case LIVE_CONTENT_NAME:
            $parserId = PARSER_LIVE_ID;
            $type = 1;
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

    try {
        $leaguesFonbet = $Parser->parseLeaguesResult($typeStr, $data);
        $eventsFonbet  = $Parser->parseEventsResult($data);
    } catch ( \Exception $e ) {
        printf("[%s] Error: %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
        exit(PHP_EOL);
    }

    $eventsByHash = [];

    foreach ( $eventsFonbet as $fbEvent ) {
        $fbEvent['name'] = preg_replace('#\s#u', ' ', $fbEvent['name']);
        $fbEvent['name'] = trim($fbEvent['name']);

        $hasMatch = preg_match('#^([A-ZА-Я]+.*?)\-([A-ZА-Я]+.*?)$#u', $fbEvent['name'], $matches);

        if( empty($hasMatch) ) {
            continue;
        }

        $date  = date('Y-m-d H:i:00', $fbEvent['startTime']);
        $team1 = trim($matches[1]);
        $team2 = trim($matches[2]);

        $fbEvent['team1'] = $team1;
        $fbEvent['team2'] = $team2;

        $hash = Helper::eventHash($fbEvent['sportId'], $date, $team1, $team2);
        $eventsByHash[$hash] = $fbEvent;
    }

    $localEvents  = $Crud->findEvents($type, $parserId, array_keys($eventsByHash), 'remote_hash');
    $localResults = $Crud->findResults(array_column($localEvents, 'id'));

    $remoteResults = [];

    foreach ( $localEvents as $hash => $event ) {
        $remoteEvent = $eventsByHash[$hash];

        try {
            $results = $Result->process($remoteEvent);
        } catch ( \Exception $e ) {
            printf("[%s] Error: При декодировании результата: %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
            continue;
        }

        if( empty($results) ) {
            continue;
        }

        $remoteResults[$hash] = $results;
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
    printf("[%s] Info: Обработка %s контента \t(%f seconds) %s", date('d/M/Y H:i:s'), $typeStr, microtime(true) - $contentTime,  PHP_EOL);
}

printf("[%s] Результаты \t(финиш) \t(%f) %s", date('d/M/Y H:i:s'), microtime(true) - $startTime, PHP_EOL);
echo PHP_EOL;

/**
 * Реиндексирование данных
 *
 * @param $arr
 * @param string $key
 * @return array
 */
function reindexByKey($arr, $key = 'id')
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