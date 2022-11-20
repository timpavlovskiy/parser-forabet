<?php
$startTime = microtime(true);
printf("[%s] Info: Загрузчик \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/Curl.php';
require_once __DIR__ . '/lib/ProxyHelper.php';
require_once __DIR__ . '/lib/Crud.php';
require_once __DIR__ . '/lib/File.php';
require_once __DIR__ . '/lib/Helper.php';

$data = [];

$proxyHelper = new ProxyHelper(getPDO());
$proxyHelper->useParserId(PARSER_LINE_ID)->initialize();

$Curl = new Curl($proxyHelper);
$Curl->enablePaidLineProxy();

$File = new File();
$Crud = new Crud(getPDO(), getRedis());



try {
    if (GOLANG_LOADER_LINE_ENABLE) {
        $needToRun = $Crud->getNeedToRunEvents(0);

        if (!empty($needToRun)) {
            $Curl->setNeedToRunEvents($needToRun);
        }
    }

    $data = $Curl->loadLine();

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

    if(GOLANG_LOADER_LINE_ENABLE){
        $eventIds = $Curl->getEventIds();

        printf("[%s] В загрузчик Go попало %s событий %s", date('d/M/Y H:i:s'), count($eventIds), PHP_EOL);
        if(!empty($eventIds)){
            $eventIds = array_values(array_map(function ($e){return (string)$e;},$eventIds));
            $Redis->publish(GOLANG_LOADER_LINE_PUB_SUB_EVENT_IDS_KEY,json_encode($eventIds));
        }
    }
} catch ( \Exception $e ) {
    printf("[%s] Error: %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
} finally {
    $File->save(__DIR__ . '/data/' . LINE_CONTENT_NAME . '.json', $data);
}

printf("[%s] Info: Загрузчик \t(%f seconds) %s", date('d/M/Y H:i:s'), microtime(true) - $startTime, PHP_EOL);
echo PHP_EOL;