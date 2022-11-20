<?php

namespace PaserFonbet\language\entity;


final class Event
{
    const MAIN_ID = 'mainId';
    const REMOTE_ID = 'remoteId';
    const TEAM1 = 'team1';
    const TEAM2 = 'team2';
    const LANGUAGE = 'language';


    private function __construct()
    {
    }

    public static function newEvent(int $remoteId, string $team1, string $team2, string $language)
    {
        return [
            self::REMOTE_ID => $remoteId,
            self::TEAM1 => $team1,
            self::TEAM2 => $team2,
            self::LANGUAGE => $language,
        ];
    }

    public static function newEventAllFields(int $mainId, int $remoteId, string $team1, string $team2, string $language)
    {
        return [
            self::MAIN_ID => $mainId,
            self::REMOTE_ID => $remoteId,
            self::TEAM1 => $team1,
            self::TEAM2 => $team2,
            self::LANGUAGE => $language,
        ];
    }
}