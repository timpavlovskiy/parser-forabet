<?php
namespace PaserFonbet;

use Exception;

require dirname(__DIR__) . '/site-main/backend/config/config.external.php';
require __DIR__ . '/language/Logger.php';
require __DIR__ . '/language/Loader.php';
require __DIR__ . '/language/Parser.php';
require __DIR__ . '/language/Storage.php';
require __DIR__ . '/language/entity/League.php';
require __DIR__ . '/language/entity/Event.php';

const UPLOAD_HOST_LINE = 'http://line04.by0e87-resources.by';
const UPLOAD_HOST_LIVE = 'http://line03.by0e87-resources.by';

const PARSER_ID_PREMATCH = 5;
const PARSER_ID_LIVE = 6;

const LANGUAGE_RU = 'ru';
const LANGUAGE_EN = 'en';

$parsersWithUrls = [
    PARSER_ID_PREMATCH => [
        LANGUAGE_RU => sprintf('%s/events/list?lang=%s', UPLOAD_HOST_LINE, LANGUAGE_RU),
        LANGUAGE_EN => sprintf('%s/events/list?lang=%s', UPLOAD_HOST_LINE, LANGUAGE_EN),
    ],
    PARSER_ID_LIVE => [
        LANGUAGE_RU => sprintf('%s/events/list?lang=%s', UPLOAD_HOST_LIVE, LANGUAGE_RU),
        LANGUAGE_EN => sprintf('%s/events/list?lang=%s', UPLOAD_HOST_LIVE, LANGUAGE_EN),
    ]
];

$logger = new language\Logger();

foreach ($parsersWithUrls as $parserId => $urls) {
    try {

        $loader = new language\Loader(clone $logger);
        $storage = new language\Storage(clone $logger, getPDO());
        $parser = new language\Parser(clone $logger, $storage->getSportList());

        foreach ($urls as $language => $url) {

            $logger->resetStartTime()->info("Загрузка языка: {$language}");
            $response = $loader->load($url);

            if (!empty($response)) {
                $logger->showSeconds()->info("Загрузка языка: {$language} завершена!");
            } else {
                $logger->showSeconds()->error("Не удалось загрузить язык: {$language}");
                continue;
            }

            $logger->resetStartTime()->info("Парсинг языка: {$language}");
            $parser->setLanguage($language)
                ->setSource($response)
                ->parse();
            $logger->showSeconds()->info("Парсинг языка: {$language} завершен!");
        }

        /**
         * Получаем турниры и события в нужном формате
         */
        $leagues = $parser->getLeagues();
        $events = $parser->getEvents();

        $logger->resetStartTime()->info('Сохранение данных');
        $storage
            ->setParserId($parserId)
            ->setLeagues($leagues)
            ->setEvents($events);

        if ($storage->save()) {
            $logger->showSeconds()->info('Данные сохранены успешно');
        } else {
            $logger->showSeconds()->info('Ошибка при сохранении!');
        }

    } catch (Exception $exception) {
        $logger->error($exception->getMessage());
    }
}




























