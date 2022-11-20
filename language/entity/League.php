<?php


namespace PaserFonbet\language\entity;


final class League
{
    const MAIN_ID = 'mainId';
    const REMOTE_ID = 'remoteId';
    const NAME = 'name';
    const LANGUAGE = 'language';
    const SPORT_NAME = 'sportName';


    private function __construct()
    {
    }

    public static function newLeague(int $remoteId, string $name, string $language, string $sportName = '')
    {
        return [
            self::REMOTE_ID => $remoteId,
            self::NAME => $name,
            self::LANGUAGE => $language,
            self::SPORT_NAME=> $sportName
        ];
    }
}