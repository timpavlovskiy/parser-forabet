<?php


namespace PaserFonbet\language;


use \Exception;
use PaserFonbet\language\entity\{Event,League};

class Parser
{

    private $source;

    private $language;

    /**@var Logger */
    private $loader;

    private $skippedSports = [];
    /**
     * key - лига id value - регулярка
     * @var array
     */
    private $leagueReplaceTemplates = [];


    private $matchSportPattern;
    private $hasCybersportSportNameMap = [];
    private $hasCybersportIdMap = [];

    private $leagues = [];
    private $events = [];

    public function __construct(Logger $loader, array $sportNames)
    {
        $this->loader = $loader;

        $this->generateMatchSportPattern($sportNames);
    }

    private function generateMatchSportPattern(array $sportNames)
    {

        $this->hasCybersportSportNameMap = [];
        foreach ($sportNames as $sportName) {
            $this->hasCybersportSportNameMap[$sportName] = false;
        }
        $sportAliases = [
            'Dota-2' => 'Dota 2',
            'Counter-Strike' => 'CSGO',
            'StarCraft 2' => 'SC2',
            'StarCraft' => 'SC2',
            'LoL' => 'League of Legends',
            'LOL' => 'League of Legends',
            'Баскетбол 3x3' => 'Баскетбол',
            'Единоборства' => 'MMA',
            'Наст. теннис' => 'Настольный теннис',
            'Жен. Киберволейбол' => 'Киберволейбол'
        ];
        foreach ($sportAliases as $aliasName => $sportName) {
            if (!isset($sportMap[$sportName])) {
                continue;
            }
            $this->hasCybersportSportNameMap[$aliasName] = false;
        }
        foreach ($this->hasCybersportSportNameMap as $sportName => $value) {
            $cyberName = ucfirst('Кибер' . mb_strtolower($sportName));
            $this->hasCybersportSportNameMap[$cyberName] = true;
        }
        $this->matchSportPattern = '#^(' . implode('|', array_keys($this->hasCybersportSportNameMap)) . ')\.#su';
    }

    public function setSource($source): self
    {
        $this->source = $source;
        return $this;
    }


    public function setLanguage($language): self
    {
        $this->language = $language;
        return $this;
    }

    private function isGenerateLeaguePatterns(): bool
    {
        return $this->language === 'ru';
    }

    private function isParseSkippedLeagues(): bool
    {
        return (!empty($this->leagueReplaceTemplates) && !empty($this->skippedSports));
    }

    private function isCybersportByName($sportName): bool
    {
        return isset($this->hasCybersportSportNameMap[$sportName])
            && !empty($this->hasCybersportSportNameMap[$sportName]);
    }

    private function isCybersportBySportId($sportId): bool
    {
        return isset($this->hasCybersportIdMap[$sportId])
            && !empty($this->hasCybersportIdMap[$sportId]);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function parse(): bool
    {
        $json = json_decode($this->source, true);
        if (empty($json)) {
            return false;
        }
        /**
         * парсим турниры
         */
        $sports = $json['sports'] ?? [];
        foreach ($sports as $sport) {
            if (!isset($sport['parentId'])) {
                continue;
            }
            if ($this->isGenerateLeaguePatterns()) {
                $this->generateReplaceTemplate($sport);
            }
            $this->skippedSports[] = ['sport' => $sport, 'language' => $this->language];
        }
        if ($this->isParseSkippedLeagues()) {
            foreach ($this->skippedSports as $skippedSport) {
                $this->parseLeagues($skippedSport['sport'], $skippedSport['language']);
            }
        }
        /**
         * парсим события
         */
        $events = $json['events'] ?? [];
        foreach ($events as $k => $event) {
            $this->parseEvent($event);
        }
        return true;
    }

    private function generateReplaceTemplate($sport)
    {
        $leagueName = $sport['name'];
        $sportIgnoreCount = 0;
        $sportName = '';
        do {
            $hasMatch = preg_match($this->matchSportPattern, $leagueName, $match) > 0;
            if ($hasMatch) {
                $sportIgnoreCount++;
                $sportName = $match[1];
                $leagueName = preg_replace('#(' . $sportName . ')\.\s?#su', '', $leagueName);
            }
        } while ($hasMatch === true);
        $patternLeague = '(:?[^\.]+)?';
        $count = substr_count($leagueName, '.');
        if ($count > 0) {
            $patternLeague = implode('', array_fill(0, $count, '[^\.]+\.?')) . $patternLeague;
        }
        $patternIgnoreSportName = '';
        if ($sportIgnoreCount - 1 > 0) {
            $patternIgnoreSportName = implode('', array_fill(0, $sportIgnoreCount - 1, '[^\.]+\.\s?'));
        }
        $patternSportName = '.*?';
        $this->leagueReplaceTemplates[$sport['id']] = "#{$patternIgnoreSportName}({$patternSportName})\.(?:\s+)?({$patternLeague})(?:\s+)?$#su";
        $this->hasCybersportIdMap[$sport['id']] = $this->isCybersportByName($sportName);
    }

    private function parseLeagues(array $sport, $language)
    {
        $fonbetSportId = $sport['id'];
        $pattern = $this->leagueReplaceTemplates[$fonbetSportId] ?? '';
        if (empty($pattern)) {
            return;
        }
        preg_match($pattern, $sport['name'], $matches);
        $sportName = $matches[1] ?? '';
        $leagueName = $matches[2] ?? '';

        if (empty($sportName) || empty($leagueName)) {
            return;
        }
        $leagueName = preg_replace(['/Жен\./isu', '/Wom\./isu', '/Муж\./isu'], ['Женщины.', 'Women.', 'Мужчины.'], $leagueName);
        /**
         * Турнир должен заканчиваться символом '.'
         */
        if (!preg_match('#\.$#', $leagueName)) {
            $leagueName = $leagueName . '.';
        }

        /**
         * Для кибер дисциплин в название турнира дописываем (киберфутбол, киберхоккей, кибертеннис)
         */
        if ($this->isCybersportBySportId($fonbetSportId)) {
            $leagueName = $sportName . '. ' . $leagueName;
        }
        $this->leagues[] = League::newLeague(
            $fonbetSportId,
            $leagueName,
            $language,
            $sportName
        );
    }

    /**
     * @param array $event
     * @throws Exception
     */
    private function parseEvent(array $event)
    {
        if ($event['level'] !== 1) {
            return;
        }
        if (empty($event['team1Id']) && empty($event['team2Id'])) {
            return;
        }

        $team1 = $event['team1'] ?? '';
        $team2 = $event['team2'] ?? '';
        if ($event['name'] !== '') {
            $team2 = "{$team2} ({$event['name']})";
        }
        if (empty($team1) && empty($team2)) {
            throw new Exception('Команды team1 и team2 пусты');
        }

        $remoteId = $event['id'] ?? 0;
        if (empty($remoteId)) {
            throw new Exception('Нет remoteId');
        }

        $this->events[] = Event::newEvent(
            $remoteId,
            $team1,
            $team2,
            $this->language
        );
    }

    public function getLeagues(): array
    {
        return array_unique($this->leagues, SORT_REGULAR);
    }

    public function getEvents(): array
    {
        return array_unique($this->events, SORT_REGULAR);
    }

}