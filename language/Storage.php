<?php

namespace PaserFonbet\language;

use PaserFonbet\language\entity\{Event,League};

class Storage
{
    /**@var array */
    private $leagues;
    /**@var array */
    private $events;
    /**@var int */
    private $parserId;

    /**@var Logger */
    private $loader;

    private $isUpdate = true;

    private $pdo;

    /**
     * Loader constructor.
     * @param Logger $loader
     */
    public function __construct(Logger $loader, \PDO $pdo)
    {
        $this->loader = $loader;
        $this->pdo = $pdo;
    }

    /**
     * @param bool $isUpdate
     * @return self
     */
    public function setIsUpdate(bool $isUpdate): self
    {
        $this->isUpdate = $isUpdate;
        return $this;
    }


    /**
     * @param array $leagues
     * @return self
     */
    public function setLeagues(array $leagues): self
    {
        $this->leagues = $leagues;
        return $this;
    }

    /**
     * @param array $events
     * @return self
     */
    public function setEvents(array $events): self
    {
        $this->events = $events;
        return $this;
    }

    /**
     * @param int $parserId
     * @return self
     */
    public function setParserId(int $parserId): self
    {
        $this->parserId = $parserId;
        return $this;
    }


    public function getSportList(): array
    {
        $list = $this->pdo->query('SELECT `name` FROM sport')->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($list)) {
            return [];
        }
        return array_column($list, 'name');
    }

    private function findRemoteIdMap(array $idList, string $tableName)
    {
        $map = [];
        if (empty($idList)) {
            return $map;
        }
        $remoteInGroup = implode(',', $idList);
        $sql = "SELECT `id`, `remote_id`  FROM {$tableName} WHERE `remote_id` IN ({$remoteInGroup}) " .
            "AND `parser_id` = {$this->parserId}";
        $this->pdo->query($sql)
            ->fetchAll(\PDO::FETCH_FUNC, function ($id, $remoteId) use (&$map) {
                $map[$remoteId] = (int)$id;
            });
        return $map;
    }

    private function findExistsLeagueRemoteIdMap(array $idList): array
    {
        return $this->findRemoteIdMap($idList, '`league`');
    }

    private function findExistsLeagueTransMap(array $idList): array
    {
        $map = [];
        if (empty($idList)) {
            return $map;
        }
        $leagueInGroup = implode(',', $idList);
        $sql = "SELECT `id`, `lang`, `name` FROM `league_tr` WHERE `id` IN ({$leagueInGroup})";
        $this->pdo->query($sql)
            ->fetchAll(\PDO::FETCH_FUNC, function ($id, $lang, $name) use (&$map) {
                $map[$lang][$id] = $name;
            });
        return $map;
    }

    private function findExistsEventRemoteIdMap(array $idList): array
    {
        return $this->findRemoteIdMap($idList, '`event`');
    }

    private function findExistsEventTransMap(array $idList): array
    {
        $map = [];
        if (empty($idList)) {
            return $map;
        }
        $leagueInGroup = implode(',', $idList);
        $sql = "SELECT `id`, `lang`, `team1`, `team2` FROM `event_tr` WHERE `id` IN ({$leagueInGroup})";
        $this->pdo->query($sql)
            ->fetchAll(\PDO::FETCH_FUNC, function ($id, $lang, $team1, $team2) use (&$map) {
                $map[$lang][$id] = Event::newEventAllFields(
                    $id,
                    0,
                    $team1,
                    $team2,
                    $lang
                );
            });
        return $map;
    }


    public function save()
    {
        if (empty($this->leagues)) {
            $this->loader->info('Турниры не добавлены');
        }
        if (empty($this->events)) {
            $this->loader->info('События не добавлены');
        }
        if (empty($this->parserId)) {
            $this->loader->info('Не установлен id парсера');
            return false;
        }
        if (empty($this->leagues) && empty($this->events)) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $isSave = $this->saveLeague();
            if (!$isSave) {
                $this->pdo->rollBack();
                return false;
            }
            $isSave = $this->saveEvents();
            if (!$isSave) {
                $this->pdo->rollBack();
                return false;
            }
            $this->pdo->commit();
        } catch (\Exception $exception) {
            $this->pdo->rollBack();
            return false;
        }

        return true;
    }


    private function saveLeague()
    {
        $leagueIdList = array_unique(array_column($this->leagues, League::REMOTE_ID));
        $leagueRemoteIdMap = $this->findExistsLeagueRemoteIdMap($leagueIdList);
        $existsLeagues = [];
        foreach ($this->leagues as &$league) {
            $remoteId = $league[League::REMOTE_ID];
            if (isset($leagueRemoteIdMap[$remoteId])) {
                $league[League::MAIN_ID] = $leagueRemoteIdMap[$remoteId];
                $existsLeagues[] = $league;
            }
        }
        $leagueTransMap = $this->findExistsLeagueTransMap($leagueRemoteIdMap);
        $insert = [];
        $case = [];
        $updateIds = [];
        foreach ($existsLeagues as $league) {
            $mainId = $league[League::MAIN_ID];
            $lang = $league[League::LANGUAGE];
            $name = $league[League::NAME];
            // добавляем и обновляем
            if (!isset($leagueTransMap[$lang][$mainId])) {
                $insert[] = "({$mainId},{$this->pdo->quote($lang)},{$this->pdo->quote($name)})";
            } elseif ($leagueTransMap[$lang][$mainId] !== $name && $this->isUpdate) {
                $case[] = "WHEN id = {$mainId} AND lang = {$this->pdo->quote($lang)} THEN {$this->pdo->quote($name)}";
                $updateIds[] = $mainId;
            }
        }
        if (!empty($insert)) {
            $values = implode(',', $insert);
            $insertSQL = "INSERT INTO league_tr(id,lang,`name`) VALUES {$values};";
            if (!$this->pdo->exec($insertSQL)) {
                return false;
            }
        }
        if (!empty($case)) {
            $caseBlock = implode(' ', $case);
            $idBlock = implode(',', $updateIds);
            $updateSQL = "UPDATE league_tr SET `name`= CASE {$caseBlock} ELSE `name` END WHERE id IN ({$idBlock});";
            if (!$this->pdo->exec($updateSQL)) {
                return false;
            }
        }
        return true;
    }

    private function saveEvents()
    {
        $eventIdList = array_unique(array_column($this->events, Event::REMOTE_ID));
        $eventRemoteIdMap = $this->findExistsEventRemoteIdMap($eventIdList);
        $existsEvents = [];
        foreach ($this->events as &$event) {
            $remoteId = $event[Event::REMOTE_ID];
            if (isset($eventRemoteIdMap[$remoteId])) {
                $event[Event::MAIN_ID] = $eventRemoteIdMap[$remoteId];
                $existsEvents[] = $event;
            }
        }
        $eventTransMap = $this->findExistsEventTransMap($eventRemoteIdMap);
        $insert = [];
        $caseTeam1 = [];
        $caseTeam2 = [];
        $updateIds = [];
        foreach ($existsEvents as $event) {
            $mainId = $event[Event::MAIN_ID];
            $lang = $event[Event::LANGUAGE];
            $team1 = $event[Event::TEAM1];
            $team2 = $event[Event::TEAM2];
            // добавляем и обновляем
            if (!isset($eventTransMap[$lang][$mainId])) {
                $insert[] = "({$mainId},{$this->pdo->quote($lang)},{$this->pdo->quote($team1)},{$this->pdo->quote($team2)})";
            } elseif($this->isUpdate) {
                if ($team1 !== $eventTransMap[$lang][$mainId][Event::TEAM1]) {
                    $caseTeam1[] = "WHEN id = {$mainId} AND lang = {$this->pdo->quote($lang)} THEN {$this->pdo->quote($team1)}";
                    $updateIds[] = $mainId;
                }
                if ($team2 !== $eventTransMap[$lang][$mainId][Event::TEAM2]) {
                    $caseTeam2[] = "WHEN id = {$mainId} AND lang = {$this->pdo->quote($lang)} THEN {$this->pdo->quote($team2)}";
                    $updateIds[] = $mainId;
                }
            }
        }
        if (!empty($insert)) {
            $values = implode(',', $insert);
            $insertSQL = "INSERT INTO event_tr (id,lang,`team1`,`team2`) VALUES {$values};";
            if (!$this->pdo->exec($insertSQL)) {
                return false;
            }
        }
        if (!empty($updateIds)) {
            $caseBlock = implode(' ', $caseTeam1);
            $caseTeam1Block = !empty($caseTeam1) ? "`team1`= CASE {$caseBlock} ELSE `team1` END " : '';
            $caseBlock = implode(' ', $caseTeam2);
            $caseTeam2Block = !empty($caseTeam2) ? "`team2`= CASE {$caseBlock} ELSE `team2` END " : '';

            $caseBlock = array_filter(
                [$caseTeam1Block, $caseTeam2Block],
                function ($item) {
                    return !empty($item);
                }
            );
            $caseBlock = implode(',', $caseBlock);

            $idBlock = implode(',', $updateIds);
            $updateSQL = "UPDATE event_tr SET {$caseBlock} WHERE id IN ({$idBlock});";
            if (!$this->pdo->exec($updateSQL)) {
                return false;
            }
        }

        return true;
    }

}