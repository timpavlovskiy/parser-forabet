<?php
$startTime = microtime(true);
printf("[%s] Info: Обновлятор \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/Crud.php';
require_once __DIR__ . '/lib/Helper.php';
require_once __DIR__ . '/lib/Margin.php';
require_once __DIR__ . '/lib/Factors.php';
require_once __DIR__ . '/lib/Parser.php';
require_once __DIR__ . '/lib/File.php';

$data = [
    LIVE_CONTENT_NAME => []
];

$Crud = new Crud(getPDO(), getRedis());
$File = new File();

$data[LIVE_CONTENT_NAME] = $File->load(__DIR__ . '/data/' . LIVE_CONTENT_NAME . '.json', LIVE_DATA_TTL);
$data[LIVE_CONTENT_NAME] = is_array($data[LIVE_CONTENT_NAME]) ? $data[LIVE_CONTENT_NAME] : [];


if( empty($data[LIVE_CONTENT_NAME]) ) {
    $Redis->del(Factors::HKEY_VALUES);

    printf("[%s] Error: Не пришли данные %s", date('d/M/Y H:i:s'), PHP_EOL);
    exit(PHP_EOL);
}

$ignoreSports  = $Crud->getIgnoreSports();
$sportMapper   = $Crud->getSportMapper('name');
$countryMapper = $Crud->getCountryMapper('name');

/**
 * Устанавливаем маржу
 */
Margin::setConstraint($Crud->getMarginConstraint());
Margin::setDefaults($Crud->findMarkets());

foreach ($data as $typeStr => $content) {
    if( empty($content) ) {
        if ($typeStr === LIVE_CONTENT_NAME) {
            $Redis->del(Factors::HKEY_VALUES);
        }

        continue;
    }

    $contentTime = microtime(true);

    switch ($typeStr) {
        case LIVE_CONTENT_NAME:
            $parserId = PARSER_LIVE_ID;
            $type = 1;

            $redisKey = sprintf(Factors::HKEY_VALUES, $type);
            $redisTtl = LIVE_DATA_TTL;
            break;
        default:
            continue 2;
    }

    printf("[%s] Info: Обработка %s контента %s", date('d/M/Y H:i:s'), $typeStr, PHP_EOL);

    $Factors = new Factors();
    $Parser = new Parser(
        $sportMapper,
        $ignoreSports,
        $countryMapper
    );

    try {
        $sportsFonbet = $Parser->parseLeagues($typeStr, $content);
        $eventsFonbet  = $Parser->parseEvents($content, LIVE_CONTENT_NAME);
    } catch ( \Exception $e ) {
        printf("[%s] Error: %s %s", date('d/M/Y H:i:s'), $e->getMessage(), PHP_EOL);
        exit(PHP_EOL);
    }

    $localEvents = $Crud->findEvents($type, $parserId, array_keys($eventsFonbet));
    $localMarkets = $Crud->findMarketMargin(array_column($localEvents, 'id'));

    $statusValues = [];

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
        $localMarket = $localMarkets[$localEvent['id']] ?? [];

        /**
         * Разбираем кэфы
         */
        $Margin = (new Margin())
            ->setMargins($localMarket, $localEvent);

        $Factors
            ->setMargin($Margin)
            ->decode($fbEvent, $localEvent);

        $statusValues[$localEvent['id']] = [
            'new' => ($Factors->hasFactors($localEvent['id']) ? 2 : 1),
            'old' => $localEvent['status']
        ];
    }

    $redisTime = microtime(true);
    printf("[%s] Info: Работа с Redis \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

    $Redis->multi();

    $Redis->del($redisKey);
    $Redis->hMset($redisKey, $Factors->getRedisValues(true));
    $Redis->expire($redisKey, $redisTtl);

    $Redis->exec();

    printf("[%s] Info: Работа с Redis \t(%f seconds) %s", date('d/M/Y H:i:s'), microtime(true) - $redisTime, PHP_EOL);

    $dbTime = microtime(true);
    printf("[%s] Info: Работа с БД \t(старт) %s", date('d/M/Y H:i:s'), PHP_EOL);

    $oldStatusValues = unserialize($Redis->get(sprintf(Factors::KEY_STATUSES, $type)));
    $oldStatusValues = is_array($oldStatusValues) ? $oldStatusValues : [];

    $finishedEvents = [];

    if( !empty($oldStatusValues) ) {
        $finishedEvents = array_diff_key($oldStatusValues, $statusValues);
    }

    $Crud->updateMultipleStatusAndDisableOld($type, $parserId, $statusValues, $finishedEvents);

    $Redis->set(sprintf(Factors::KEY_STATUSES, $type), serialize($statusValues));

    printf("[%s] Info: Работа с БД \t(%f seconds) %s", date('d/M/Y H:i:s'), microtime(true) - $dbTime, PHP_EOL);
    printf("[%s] Info: Обработка %s контента \t(%f seconds) %s", date('d/M/Y H:i:s'), $typeStr, microtime(true) - $contentTime,  PHP_EOL);
}

printf("[%s] Info: Обновлятор \t(%f seconds) %s", date('d/M/Y H:i:s'), microtime(true) - $startTime, PHP_EOL);
echo PHP_EOL;