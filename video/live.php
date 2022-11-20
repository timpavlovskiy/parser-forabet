<?php
$startTime = microtime(true);
printf("[%s] Info: Трансляции \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/lib/Crud.php';
require_once dirname(__DIR__) . '/lib/Parser.php';
require_once dirname(__DIR__) . '/lib/Factors.php';
require_once dirname(__DIR__) . '/lib/Result.php';

$Crud = new Crud(getPDO(), getRedis());

$data = unserialize($Redis->get('Fonbet:Data:' . LIVE_CONTENT_NAME));
$data = is_array($data) ? $data : [];

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
$localVideos = $Crud->findVideos(array_column($localEvents, 'id'));

$remoteVideo = [];

foreach ( $eventsFonbet as $fbEventCode => $fbEvent ) {
    /**
     * Если пришло событие которого нету в базе тогда не обрабатываем его
     */
    if ( !isset($localEvents[$fbEventCode]) ) {
        continue;
    }

    $localEvent = $localEvents[$fbEventCode];
    $localVideo = $localVideos[$localEvent['id']] ?? [];

    /**
     * Получаем ссылку на трансляцию
     */
    $url = $Result->getVideo($fbEvent);

    if( empty($url) ) {
        continue;
    }

    $remoteVideo[$fbEventCode] = [
        'eventId'      => $localEvent['id'],
        'sportId'      => $localEvent['sportId'],
        'idRemote'     => $fbEventCode,
        'url'          => $url,
        'team1'        => $localEvent['team1'],
        'team2'        => $localEvent['team2']
    ];
}

$Parser->processVideos($localEvents, $remoteVideo, $localVideos);
$newVideos = $Parser->getNewVideos();
$updateVideos = $Parser->getUpdateVideos();

$dbTime = microtime(true);
printf("[%s] Info: Работа с БД \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

try {
    $Crud->updateVideos($updateVideos);
} catch ( \Exception $e ) {
    printf("[%s] Error: Во время обновления видеотрансляций произошла ошибка %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
    exit(PHP_EOL);
}

try {
    $Crud->addVideos($newVideos);
} catch ( \Exception $e ) {
    printf("[%s] Error: Во время добавления видеотрансляций произошла ошибка %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
    exit(PHP_EOL);
}

printf("[%s] Info: Работа с БД \t(%f seconds) %s", date('d/M/Y H:i:s'), microtime(true) - $dbTime, PHP_EOL);
printf("[%s] Трансляции \t(финиш) \t(%f) %s", date('d/M/Y H:i:s'), microtime(true) - $startTime, PHP_EOL);
echo PHP_EOL;
