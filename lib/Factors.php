<?php

class Factors
{
    const HKEY_VALUES = 'RM:Factors:%s:fonbet';
    const KEY_STATUSES = 'Fonbet:Statuses:%s';

	/**
	 * Размещение коэффициентов
	 */
	const HEAD = 0;
	const BODY = 1;

	/**
	 * Логическое группирование коэффициентов
	 */
	const F_OUTCOMES       = 1;
	const F_DBL_CHANCE     = 2;
	const F_FORA           = 3;
	const F_TOTAL          = 4;
	const F_I_TOTAL        = 5;
	const F_TEAM           = 6;
	const F_ETEAM          = 7;
	const F_BOOL           = 8;
	const F_I_BOOL         = 9;
	const F_SCORES         = 10;
	const F_GAMES          = 11;
	const F_EFFECTIVE      = 12;
	const F_PERIOD_M       = 13;
    const F_WINNER_YN      = 14;

    /**
     * Контекст коэффициента
     */
    const CONTEXT_MAIN                  = 'score';
    const CONTEXT_ACE                   = 'ace';
    const CONTEXT_OVERTIME              = 'overtime';
    const CONTEXT_ADD_TIME              = 'add_time';
    const CONTEXT_CORNERS               = 'corners';
	const CONTEXT_CORNERS_ADD_TIME      = 'corners_add_time';
	const CONTEXT_YELLOW_CARDS          = 'yellow_cards';
	const CONTEXT_YELLOW_CARDS_ADD_TIME = 'yellow_cards_add_time';
    const CONTEXT_SERIES_PENALTY        = 'series_penalty';
	const CONTEXT_THREE_POINT           = 'three_point';
	const CONTEXT_TWO_MIN_REMOVAL       = 'two_min_removal';
    const CONTEXT_SERIES_BULLITES       = 'series_bullites';
    const CONTEXT_ERRORS_ON_FILLING     = 'errors_on_filling';
    const CONTEXT_SHOTS_ON_GOAL         = 'shots_on_goal';
    const CONTEXT_FOULS                 = 'fouls';
    const CONTEXT_GOAL_KICKS            = 'goal_kicks';
    const CONTEXT_POWER_PLAY_GOALS      = 'power_play_goals';
    const CONTEXT_OFFSIDES              = 'offsides';
    const CONTEXT_FACE_OFFS             = 'face_offs';
    const CONTEXT_THROW_INS             = 'throw_ins';

	/**
	 * Виды названий коэффициентов
	 */
	const V_LOWER = 1;
	const V_UPPER = 2;

	const V_FIRST  = 1;
	const V_SECOND = 2;
	const V_DRAW   = 0;
	const V_EXCEPT = 0;

	const V_FIRST_LOWER  = 11;
	const V_FIRST_UPPER  = 12;
	const V_SECOND_LOWER = 21;
	const V_SECOND_UPPER = 22;

	const V_NO  = 1;
	const V_YES = 2;

	const V_FIRST_NO   = 11;
	const V_FIRST_YES  = 12;
	const V_SECOND_NO  = 21;
	const V_SECOND_YES = 22;

    private static $_triplePairValIndex = [
        self::V_FIRST  => [
            self::V_SECOND,
            self::V_DRAW
        ],
        self::V_SECOND => [
            self::V_FIRST,
            self::V_DRAW
        ],
        self::V_DRAW   => [
            self::V_FIRST,
            self::V_SECOND
        ],
        self::V_EXCEPT => [
            self::V_FIRST,
            self::V_SECOND
        ],
    ];

    private static $_doublePairValIndex = [
        self::V_UPPER => [
            self::V_LOWER
        ],
        self::V_LOWER => [
            self::V_UPPER
        ],
        self::V_FIRST_UPPER  => [
            self::V_FIRST_LOWER
        ],
        self::V_FIRST_LOWER  => [
            self::V_FIRST_UPPER
        ],
        self::V_SECOND_UPPER => [
            self::V_SECOND_LOWER
        ],
        self::V_SECOND_LOWER => [
            self::V_SECOND_UPPER
        ],
        self::V_YES => [
            self::V_NO
        ],
        self::V_NO  => [
            self::V_YES
        ],
        self::V_FIRST_YES  => [
            self::V_FIRST_NO
        ],
        self::V_FIRST_NO   => [
            self::V_FIRST_YES
        ],
        self::V_SECOND_YES => [
            self::V_SECOND_NO
        ],
        self::V_SECOND_NO  => [
            self::V_SECOND_YES
        ]
    ];

    /**
     * Маппер названий кэфов у контекста
     *
     * 1 - Исход         : 163 - Исход (Угловые)
     * 2 - Двойной исход : 164 - Двойной исход (Угловые)
     * 7 - Фора          : 161 - Форы (Угловые)
     * 8 - Тотал         : 160 - Тотал (Угловые)
     * 9 - Инд. тотал    : 262 - Инд. тотал (Угловые)
     */
    private $_contextNameMapper = [
        self::CONTEXT_CORNERS      => [
            1   => 163, // Исход (Угловые)
            2   => 164, // Двойной исход (Угловые)
            7   => 161, // Фора (Угловые)
            8   => 160, // Тотал (Угловые)
            9   => 262, // Инд. тотал (Угловые)
            6   => 225  // Чётный тотал (Угловые)
        ],
        self::CONTEXT_YELLOW_CARDS => [
            1   => 165, // Исход (Жёлтые карты)
            2   => 166, // Двойной исход (Жёлтые карты)
            7   => 162, // Фора (Жёлтые карты)
            8   => 159, // Тотал (Жёлтые карты)
            9   => 263, // Инд. тотал (Жёлтые карты)
            6   => 226  // Чётный тотал (Жёлтые карты)
        ],
        self::CONTEXT_OVERTIME => [
            1 => 197, // Исход (Овертайм)
            2 => 198, // Двойной исход (Овертайм)
        ],
        self::CONTEXT_SERIES_BULLITES => [
            1 => 199, // Исход (По буллитам)
            2 => 200, // Двойной исход (По буллитам)
            7 => 201, // Фора (По буллитам)
            8 => 202, // Тотал (По буллитам)
            9 => 203, // Инд. Тотал (По буллитам)
        ],
        self::CONTEXT_ADD_TIME => [
            1 => 243, // Исход (Доп. время)
            2 => 244, // Двойной исход (Доп. время)
            7 => 245, // Фора (Доп. время)
            8 => 246, // Тотал (Доп. время)
            9 => 247, // Инд. тотал (Доп. время)
            5 => 5,   // Проход
        ],
        self::CONTEXT_YELLOW_CARDS_ADD_TIME => [
            1 => 250, // Исход (Жёлтые карты в доп. время)
            2 => 251, // Двойной исход (Жёлтые карты в доп. время)
            7 => 254, // Фора (Жёлтые карты в доп. время)
            8 => 255, // Тотал (Жёлтые карты в доп. время)
            9 => 264, // Инд. тотал (Жёлтые карты в доп. время)
        ],
        self::CONTEXT_CORNERS_ADD_TIME => [
            1 => 248, // Исход (Угловые в доп. время)
            2 => 249, // Двойной исход (Угловые в доп. время)
            7 => 252, // Фора (Угловые в доп. время)
            8 => 253, // Тотал (Угловые в доп. время)
            9 => 265, // Инд. тотал (Угловые в доп. время)
        ],
        self::CONTEXT_SERIES_PENALTY => [
            1 => 256, // Исход (Серия пенальти)
            2 => 257, // Двойной исход (Серия пенальти)
            7 => 258, // Фора (Серия пенальти)
            8 => 259, // Тотал (Серия пенальти)
            9 => 260, // Инд. тотал (Серия пенальти)
        ],
        self::CONTEXT_TWO_MIN_REMOVAL => [
            1   => 266, // Исход (Кол-во 2-мин удалений)
            2   => 267, // Двойной исход (Кол-во 2-мин удалений)
            7   => 268, // Фора (Кол-во 2-мин удалений)
            8   => 269, // Тотал (Кол-во 2-мин удалений)
            9   => 270, // Инд. тотал (Кол-во 2-мин удалений)
            6   => 227  // Чётный тотал (Кол-во 2-мин удалений)
        ],
        self::CONTEXT_SHOTS_ON_GOAL => [
            1   => 271, // Исход (Броски в створ)
            2   => 272, // Двойной исход (Броски в створ)
            7   => 273, // Фора (Броски в створ)
            8   => 274, // Тотал (Броски в створ)
            9   => 275, // Инд. тотал (Броски в створ)
            6   => 228  // Чётный тотал (Броски в створ)
        ],
        self::CONTEXT_ACE => [
            1   => 276, // Исход (Эйсы)
            2   => 277, // Двойной исход (Эйсы)
            7   => 278, // Фора (Эйсы)
            8   => 279, // Тотал (Эйсы)
            9   => 280, // Инд. тотал (Эйсы)
            6   => 229  // Чётный тотал (Эйсы)
        ],
        self::CONTEXT_ERRORS_ON_FILLING => [
            1   => 281, // Исход (Ошибки на подаче)
            2   => 282, // Двойной исход (Ошибки на подаче)
            7   => 283, // Фора (Ошибки на подаче)
            8   => 284, // Тотал (Ошибки на подаче)
            9   => 285, // Инд. тотал (Ошибки на подаче)
            6   => 230  // Чётный тотал (Ошибки на подаче)
        ],
        self::CONTEXT_THREE_POINT => [
            1   => 286, // Исход (Заб/3-х очковые)
            2   => 287, // Двойной исход (Заб/3-х очковые)
            7   => 288, // Фора (Заб/3-х очковые)
            8   => 289, // Тотал (Заб/3-х очковые)
            9   => 290, // Инд. тотал (Заб/3-х очковые)
            6   => 231  // Чётный тотал (Заб/3-х очковые)
        ],
        self::CONTEXT_FOULS => [
            1   => 291, // Исход (Фолы)
            2   => 292, // Двойной исход (Фолы)
            7   => 293, // Фора (Фолы)
            8   => 294, // Тотал (Фолы)
            9   => 295, // Инд. тотал (Фолы)
            6   => 232  // Чётный тотал (Фолы)
        ],
        self::CONTEXT_GOAL_KICKS => [
            1   => 326, // Исход (Удары от ворот)
            2   => 327, // Двойной исход (Удары от ворот)
            7   => 328, // Фора (Удары от ворот)
            8   => 329, // Тотал (Удары от ворот)
            9   => 330, // Инд. тотал (Удары от ворот)
            6   => 233  // Чётный тотал (Удары от ворот)
        ],
        self::CONTEXT_POWER_PLAY_GOALS => [
            1   => 331, // Исход (Голы в большинстве)
            2   => 332, // Двойной исход (Голы в большинстве)
            7   => 333, // Фора (Голы в большинстве)
            8   => 334, // Тотал (Голы в большинстве)
            9   => 335, // Инд. тотал (Голы в большинстве)
            6   => 234  // Чётный тотал (Голы в большинстве)
        ],
        self::CONTEXT_OFFSIDES => [
            1   => 336, // Исход (Офсайды)
            2   => 337, // Двойной исход (Офсайды)
            7   => 338, // Фора (Офсайды)
            8   => 339, // Тотал (Офсайды)
            9   => 340, // Инд. тотал (Офсайды)
            6   => 235  // Чётный тотал (Офсайды)
        ],
        self::CONTEXT_FACE_OFFS => [
            1   => 341, // Исход (Вбрасывания)
            2   => 342, // Двойной исход (Вбрасывания)
            7   => 343, // Фора (Вбрасывания)
            8   => 344, // Тотал (Вбрасывания)
            9   => 345, // Инд. тотал (Вбрасывания)
            6   => 236  // Чётный тотал (Вбрасывания)
        ],
        self::CONTEXT_THROW_INS => [
            1   => 346, // Исход (Вброс аутов)
            2   => 347, // Двойной исход (Вброс аутов)
            7   => 348, // Фора (Вброс аутов)
            8   => 349, // Тотал (Вброс аутов)
            9   => 350, // Инд. тотал (Вброс аутов)
            6   => 237  // Чётный тотал (Вброс аутов)
        ]
    ];

    /**
     * Массив с определением каждого коэффициента
     */
    public static $decodeValues;

    /**
     * Все коэффициенты с уникальным адресом
     */
    private $redisValues = [];

    /**
     * Список не опознанных маркетов
     */
    private $_newMarkets = [];

    /**
     * Определение коэффициента
     */
    private $definitions = [];

    private $_event;

    private $_eventId;

    private $_sportId;

    /**
     * @var Margin
     */
    private $_margin;

    /**
     * Настройки маржи для многострочные (фора, тоталы, инд. тоталы)
     */
    const TAIL_MARGIN_STEP = 0.5;
    const TAIL_MARGIN_MAX = 12;

    /**
     * Factors constructor.
     */
    public function __construct()
    {
        $this->_newMarkets = $this->_loadNewMarkets();
    }

    public function __destruct()
    {
        $this->_saveNewMarkets();
    }

    private function _loadNewMarkets()
    {
        $filePath = dirname(__DIR__) . '/markets.json';
        $content = null;

        if( file_exists($filePath) ) {
            $content = file_get_contents($filePath);
        }

        return json_decode($content, true) ?? [];
    }

    private function _saveNewMarkets()
    {
        $filePath = dirname(__DIR__) . '/markets.json';

        ksort($this->_newMarkets);

        foreach ( $this->_newMarkets as $sportName => $markets ) {
            sort($markets);
            $this->_newMarkets[$sportName] = $markets;
        }

        file_put_contents($filePath, json_encode($this->_newMarkets, JSON_UNESCAPED_UNICODE));
    }

    private function _makeNewMarket($sportName, $marketName)
    {
        if( !isset($this->_newMarkets[$sportName]) ) {
            $this->_newMarkets[$sportName] = [];
        }

        if( array_search($marketName, $this->_newMarkets[$sportName]) === false ) {
            array_push($this->_newMarkets[$sportName], $marketName);
        }
    }

    /**
     * @param bool $serialized
     * @return array
     */
    public function getRedisValues($serialized = false)
    {
        if ( $serialized ) {
            $out = [];

            foreach ( $this->redisValues as $eventId => &$values ) {
                $out[$eventId] = serialize($values);
            }

            return $out;
        }

        return $this->redisValues;
    }

    /**
     * @param $eventId
     * @return bool
     */
    public function hasFactors($eventId)
    {
        return !empty($this->redisValues[$eventId]);
    }

    /**
     * @param Margin $margin
     * @return Factors
     */
    public function setMargin(Margin  $margin): Factors
    {
        $this->_margin = $margin;

        return $this;
    }

    /**
     * @param $fonbetEvent
     * @param $localEvent
     * @return void
     */
	public function decode($fonbetEvent, $localEvent)
	{
        $this->_event = $localEvent;

        $this->_eventId = $eventId = (int) $localEvent['id'];
        $this->_sportId = $sportId = (int) $localEvent['sportId'];

        $outcomeWin   = [];
        $outcomeTails = [];
        $factorExists = [];

        /**
         * Запускаем перебор коэффициентов для каждого периода события
         */
        foreach ( $fonbetEvent['periods'] as $values ) {
            $factors = $values['factors'] ?? [];

            //уходим на следующий оборот если нет коэффициентов
            if (!isset($factors)) {
                continue;
            }

            ksort($factors);
            $isEventContextChanged = false;

            // внутреннее ли это событие (угловые, 3-ёх очковые и т.д)
            if ($values['context'] !== self::CONTEXT_MAIN) {
                if ( !isset($this->_contextNameMapper[$values['context']]) ) {
                    continue;
                }

                if( $sportId === 1 ) {
                    if( array_search($values['context'], [self::CONTEXT_SHOTS_ON_GOAL, self::CONTEXT_THROW_INS, self::CONTEXT_GOAL_KICKS]) !== false ) {
                        continue;
                    }
                }

                $isEventContextChanged = true;
            }

            /**
             * Проверяем раскодировку кэфов и распаковываем кэфы по необходимости
             */
            foreach ($factors as $fCode => $factor) {
                if (!isset(self::$decodeValues[$fCode])) {
                    $this->_makeNewMarket($values['sportName'], $fCode);
                    continue;
                }

                $isNewMarket = array_search($fCode, $this->_newMarkets[$values['sportName']] ?? []);

                if ($isNewMarket !== false) {
                    unset($this->_newMarkets[$values['sportName']][$isNewMarket]);
                }

                $period = (int)$values['period'];
                $context = $values['context'];

                $definitions = self::$decodeValues[$fCode];

                /**
                 * При необходимости игнорим маркет
                 */
                if (!empty($definitions['ignore'])) {
                    continue;
                }

                if (isset($definitions['period'])) {
                    $period = (int)$definitions['period'];
                }

                /**
                 * Если это внутреннее события тогда изменяем номера коэффициентов
                 */
                if ($isEventContextChanged) {
                    if (!isset($this->_contextNameMapper[$values['context']][$definitions[2]])) {
                        continue;
                    }

                    $definitions[2] = $this->_contextNameMapper[$values['context']][$definitions[2]];
                }

                $fGroupCode = $definitions[1];
                $fName = $definitions[2];

                if (!isset($factorExists[$period][$fName])) {
                    $factorExists[$period][$fName] = [
                        'fGroupCode' => $fGroupCode,
                        'fName'      => $fName,
                        'period'     => $period,
                        'context'    => $context,
                        'values'     => []
                    ];
                }

                $factorExists[$period][$fName]['values'][] = $factor;
            }
        }

        /**
         * Запускаем перебор коэффициентов события по типам
         */
        foreach ($factorExists as $period => $fNames) {
            foreach ($fNames as $fName => $factor) {
                $fGroupCode   = $factor['fGroupCode'];
                $fName        = $factor['fName'];
                $period       = $factor['period'];
                $context      = $factor['context'];
                $factorValues = $factor['values'];

                switch ($fGroupCode) {
                    case self::F_OUTCOMES:

                        $values = [];

                        foreach ($factorValues as $factorValue) {
                            $this->definitions = $definitions = self::$decodeValues[$factorValue['f']];
                            $this->definitions['b'] = $definitions['b'] = $factorValue['b'];
                            $this->definitions['ctx'] = $definitions['ctx'] = $context;

                            $fValue = $factorValue['v'] ?? null;

                            if (is_null($fValue)) {
                                continue;
                            }

                            $fValIndex = $definitions[3];
                            $fValName = $definitions['vn'];

                            $id = $this->_genHash([$period, $fGroupCode, $fName, null, $fValIndex]);

                            $values[$fValIndex] = [
                                'v'  => $fValue,
                                'i'  => $fValIndex,
                                'id' => $id,
                                'pt' => null,
                                'vn' => $fValName
                            ];
                        }

                        $this->calculateMargin3($eventId, [
                            'name'   => $fName,
                            'period' => $period,
                            'v1'     => $values[self::V_FIRST]['v'] ?? null,
                            'v2'     => $values[self::V_SECOND]['v'] ?? null,
                            'v3'     => $values[self::V_DRAW]['v'] ?? null,
                            'i1'     => $values[self::V_FIRST]['i'] ?? null,
                            'i2'     => $values[self::V_SECOND]['i'] ?? null,
                            'i3'     => $values[self::V_DRAW]['i'] ?? null,
                            'hash1'  => $values[self::V_FIRST]['id'] ?? null,
                            'hash2'  => $values[self::V_SECOND]['id'] ?? null,
                            'hash3'  => $values[self::V_DRAW]['id'] ?? null,
                            'vn1'    => $values[self::V_FIRST]['vn'] ?? null,
                            'vn2'    => $values[self::V_SECOND]['vn'] ?? null,
                            'vn3'    => $values[self::V_DRAW]['vn'] ?? null,
                            'pt1'    => $values[self::V_FIRST]['pt'] ?? null,
                            'pt2'    => $values[self::V_SECOND]['pt'] ?? null,
                            'pt3'    => $values[self::V_DRAW]['pt'] ?? null,
                        ]);

                        break;

                    case self::F_DBL_CHANCE:

                        $values = [];

                        foreach ($factorValues as $factorValue) {
                            $this->definitions = $definitions = self::$decodeValues[$factorValue['f']];
                            $this->definitions['b'] = $definitions['b'] = $factorValue['b'];
                            $this->definitions['ctx'] = $definitions['ctx'] = $context;

                            $fValue = $factorValue['v'] ?? null;

                            if (is_null($fValue)) {
                                continue;
                            }

                            $fValIndex = $definitions[3];
                            $fValName = $definitions['vn'];

                            $id = $this->_genHash([$period, $fGroupCode, $fName, null, $fValIndex]);

                            $values[$fValIndex] = [
                                'v'  => $fValue,
                                'i'  => $fValIndex,
                                'id' => $id,
                                'pt' => null,
                                'vn' => $fValName
                            ];
                        }

                        foreach ($values as $value) {
                            $this->calculateMargin1($eventId, [
                                'name'   => $fName,
                                'period' => $period,
                                'v1'     => $value['v'],
                                'i1'     => $value['i'],
                                'hash1'  => $value['id'],
                                'vn1'    => $value['vn'],
                                'pt1'    => null,
                                'is3outcomes' => true
                            ]);
                        }

                        break;

                    case self::F_FORA:
                    case self::F_TOTAL:
                    case self::F_I_TOTAL:

                        foreach ($factorValues as $factorValue) {
                            $this->definitions = $definitions = self::$decodeValues[$factorValue['f']];
                            $this->definitions['b'] = $definitions['b'] = $factorValue['b'];
                            $this->definitions['ctx'] = $definitions['ctx'] = $context;

                            $fValue = $factorValue['v'] ?? null;
                            $fPoint = $factorValue['pt'] ?? $definitions['point'] ?? null;
                            $fPoint = $this->_pointTypeHint($fPoint);

                            if (is_null($fValue) || is_null($fPoint)) {
                                continue;
                            }

                            $fValIndex = $definitions[3];
                            $id = $this->_genHash([$period, $fGroupCode, $fName, $fPoint, $fValIndex]);

                            $outcomeTails[$context][$period][$fName][] = [
                                'v'           => $fValue,
                                'i'           => $fValIndex,
                                'pt'          => $fPoint,
                                'id'          => $id,
                                'definitions' => $definitions,
                            ];
                        }

                        break;

                    case self::F_WINNER_YN:
                    case self::F_BOOL:

                        $values = [];

                        foreach ($factorValues as $factorValue) {
                            $this->definitions = $definitions = self::$decodeValues[$factorValue['f']];
                            $this->definitions['b'] = $definitions['b'] = $factorValue['b'];
                            $this->definitions['ctx'] = $definitions['ctx'] = $context;

                            $fValue = $factorValue['v'] ?? null;
                            $fPoint = $factorValue['pt'] ?? $definitions['point'] ?? null;
                            $fPoint = $this->_pointTypeHint($fPoint);

                            if (is_null($fValue)) {
                                continue;
                            }

                            $fValIndex = $definitions[3];
                            $fValName = $definitions['vn'];

                            $id = $this->_genHash([$period, $fGroupCode, $fName, $fPoint, $fValIndex]);

                            $values[$fValIndex] = [
                                'v'  => $fValue,
                                'i'  => $fValIndex,
                                'id' => $id,
                                'pt' => $fPoint,
                                'vn' => $fValName
                            ];
                        }

                        $this->calculateMargin2($eventId, [
                            'name'   => $fName,
                            'period' => $period,
                            'v1'     => $values[self::V_NO]['v'] ?? null,
                            'v2'     => $values[self::V_YES]['v'] ?? null,
                            'i1'     => $values[self::V_NO]['i'] ?? null,
                            'i2'     => $values[self::V_YES]['i'] ?? null,
                            'hash1'  => $values[self::V_NO]['id'] ?? null,
                            'hash2'  => $values[self::V_YES]['id'] ?? null,
                            'vn1'    => $values[self::V_NO]['vn'] ?? null,
                            'vn2'    => $values[self::V_YES]['vn'] ?? null,
                            'pt1'    => $values[self::V_NO]['pt'] ?? null,
                            'pt2'    => $values[self::V_YES]['pt'] ?? null
                        ]);

                        break;

                    case self::F_I_BOOL:

                        $valuesByTeams = [];
                        $numTeam = 0;

                        foreach ($factorValues as $factorValue) {
                            $this->definitions = $definitions = self::$decodeValues[$factorValue['f']];
                            $this->definitions['b'] = $definitions['b'] = $factorValue['b'];
                            $this->definitions['ctx'] = $definitions['ctx'] = $context;

                            $fValue = $factorValue['v'] ?? null;
                            $fPoint = $factorValue['pt'] ?? $definitions['point'] ?? null;
                            $fPoint = $this->_pointTypeHint($fPoint);

                            if (is_null($fValue)) {
                                continue;
                            }

                            $fValIndex = $definitions[3];
                            $fValName = $definitions['vn'];

                            if ($fValIndex === self::V_FIRST_UPPER || $fValIndex === self::V_FIRST_LOWER) {
                                $numTeam = 1;
                            }

                            if ($fValIndex === self::V_SECOND_UPPER || $fValIndex === self::V_SECOND_LOWER) {
                                $numTeam = 2;
                            }

                            if ($numTeam === 0) {
                                continue;
                            }

                            $id = $this->_genHash([$period, $fGroupCode, $fName, $fPoint, $fValIndex]);

                            $valuesByTeams[$numTeam][$fValIndex] = [
                                'v'  => $fValue,
                                'i'  => $fValIndex,
                                'id' => $id,
                                'pt' => $fPoint,
                                'vn' => $fValName
                            ];
                        }

                        foreach ($valuesByTeams as $numTeam => $values ) {
                            $index1 = null;
                            $index2 = null;

                            if ($numTeam === 1) {
                                $index1 = self::V_FIRST_YES;
                                $index2 = self::V_FIRST_NO;
                            }

                            if ($numTeam === 2) {
                                $index1 = self::V_SECOND_YES;
                                $index2 = self::V_SECOND_NO;
                            }

                            $this->calculateMargin2($eventId, [
                                'name'   => $fName,
                                'period' => $period,
                                'v1'     => $values[$index1]['v'] ?? null,
                                'v2'     => $values[$index2]['v'] ?? null,
                                'i1'     => $values[$index1]['i'] ?? null,
                                'i2'     => $values[$index2]['i'] ?? null,
                                'hash1'  => $values[$index1]['id'] ?? null,
                                'hash2'  => $values[$index2]['id'] ?? null,
                                'vn1'    => $values[$index1]['vn'] ?? null,
                                'vn2'    => $values[$index2]['vn'] ?? null,
                                'pt1'    => $values[$index1]['pt'] ?? null,
                                'pt2'    => $values[$index2]['pt'] ?? null,
                            ]);
                        }

                        break;

                    case self::F_TEAM:
                    case self::F_GAMES:

                        $values = [];

                        foreach ($factorValues as $factorValue) {
                            $this->definitions = $definitions = self::$decodeValues[$factorValue['f']];
                            $this->definitions['b'] = $definitions['b'] = $factorValue['b'];
                            $this->definitions['ctx'] = $definitions['ctx'] = $context;

                            $fValue = $factorValue['v'] ?? null;
                            $fPoint = $factorValue['pt'] ?? $definitions['point'] ?? null;
                            $fPoint = $this->_pointTypeHint($fPoint);

                            if (is_null($fValue)) {
                                continue;
                            }

                            $fValIndex = $definitions[3];
                            $fValName = $definitions['vn'];

                            $id = $this->_genHash([$period, $fGroupCode, $fName, $fPoint, $fValIndex]);

                            $values[$fValIndex] = [
                                'v'  => $fValue,
                                'i'  => $fValIndex,
                                'id' => $id,
                                'pt' => $fPoint,
                                'vn' => $fValName
                            ];
                        }

                        $this->calculateMargin2($eventId, [
                            'name'   => $fName,
                            'period' => $period,
                            'v1'     => $values[self::V_FIRST]['v'] ?? null,
                            'v2'     => $values[self::V_SECOND]['v'] ?? null,
                            'i1'     => $values[self::V_FIRST]['i'] ?? null,
                            'i2'     => $values[self::V_SECOND]['i'] ?? null,
                            'hash1'  => $values[self::V_FIRST]['id'] ?? null,
                            'hash2'  => $values[self::V_SECOND]['id'] ?? null,
                            'vn1'    => $values[self::V_FIRST]['vn'] ?? null,
                            'vn2'    => $values[self::V_SECOND]['vn'] ?? null,
                            'pt1'    => $values[self::V_FIRST]['pt'] ?? null,
                            'pt2'    => $values[self::V_SECOND]['pt'] ?? null,
                        ]);

                        break;

                    case self::F_ETEAM:

                        $values = [];

                        foreach ($factorValues as $factorValue) {
                            $this->definitions = $definitions = self::$decodeValues[$factorValue['f']];
                            $this->definitions['b'] = $definitions['b'] = $factorValue['b'];
                            $this->definitions['ctx'] = $definitions['ctx'] = $context;

                            $fValue = $factorValue['v'] ?? null;
                            $fPoint = $factorValue['pt'] ?? $definitions['point'] ?? null;
                            $fPoint = $this->_pointTypeHint($fPoint);

                            if (is_null($fValue)) {
                                continue;
                            }

                            $fValIndex = $definitions[3];
                            $fValName = $definitions['vn'];

                            $id = $this->_genHash([$period, $fGroupCode, $fName, $fPoint, $fValIndex]);

                            $values[$fValIndex] = [
                                'v'  => $fValue,
                                'i'  => $fValIndex,
                                'id' => $id,
                                'pt' => $fPoint,
                                'vn' => $fValName
                            ];
                        }

                        $this->calculateMargin3($eventId, [
                            'name'   => $fName,
                            'period' => $period,
                            'v1'     => $values[self::V_FIRST]['v'] ?? null,
                            'v2'     => $values[self::V_SECOND]['v'] ?? null,
                            'v3'     => $values[self::V_EXCEPT]['v'] ?? null,
                            'i1'     => $values[self::V_FIRST]['i'] ?? null,
                            'i2'     => $values[self::V_SECOND]['i'] ?? null,
                            'i3'     => $values[self::V_EXCEPT]['i'] ?? null,
                            'hash1'  => $values[self::V_FIRST]['id'] ?? null,
                            'hash2'  => $values[self::V_SECOND]['id'] ?? null,
                            'hash3'  => $values[self::V_EXCEPT]['id'] ?? null,
                            'vn1'    => $values[self::V_FIRST]['vn'] ?? null,
                            'vn2'    => $values[self::V_SECOND]['vn'] ?? null,
                            'vn3'    => $values[self::V_EXCEPT]['vn'] ?? null,
                            'pt1'    => $values[self::V_FIRST]['pt'] ?? null,
                            'pt2'    => $values[self::V_SECOND]['pt'] ?? null,
                            'pt3'    => $values[self::V_EXCEPT]['pt'] ?? null,
                        ]);

                        break;

                    case self::F_EFFECTIVE:

                        $values = [];

                        foreach ($factorValues as $factorValue) {
                            $this->definitions = $definitions = self::$decodeValues[$factorValue['f']];
                            $this->definitions['b'] = $definitions['b'] = $factorValue['b'];
                            $this->definitions['ctx'] = $definitions['ctx'] = $context;

                            $fValue = $factorValue['v'] ?? null;
                            $fPoint = $factorValue['pt'] ?? $definitions['point'] ?? null;
                            $fPoint = $this->_pointTypeHint($fPoint);

                            if (is_null($fValue)) {
                                continue;
                            }

                            $fValIndex = $definitions[3];
                            $fValName = $definitions['vn'];

                            $id = $this->_genHash([$period, $fGroupCode, $fName, $fPoint, $fValIndex]);

                            $values[$fValIndex] = [
                                'v'  => $fValue,
                                'i'  => $fValIndex,
                                'id' => $id,
                                'pt' => $fPoint,
                                'vn' => $fValName
                            ];
                        }

                        $this->calculateMargin3($eventId, [
                            'name'   => $fName,
                            'period' => $period,
                            'v1'     => $values[self::V_FIRST]['v'] ?? null,
                            'v2'     => $values[self::V_SECOND]['v'] ?? null,
                            'v3'     => $values[self::V_DRAW]['v'] ?? null,
                            'i1'     => $values[self::V_FIRST]['i'] ?? null,
                            'i2'     => $values[self::V_SECOND]['i'] ?? null,
                            'i3'     => $values[self::V_DRAW]['i'] ?? null,
                            'hash1'  => $values[self::V_FIRST]['id'] ?? null,
                            'hash2'  => $values[self::V_SECOND]['id'] ?? null,
                            'hash3'  => $values[self::V_DRAW]['id'] ?? null,
                            'vn1'    => $values[self::V_FIRST]['vn'] ?? null,
                            'vn2'    => $values[self::V_SECOND]['vn'] ?? null,
                            'vn3'    => $values[self::V_DRAW]['vn'] ?? null,
                            'pt1'    => $values[self::V_FIRST]['pt'] ?? null,
                            'pt2'    => $values[self::V_SECOND]['pt'] ?? null,
                            'pt3'    => $values[self::V_DRAW]['pt'] ?? null,
                        ]);

                        break;

                    case self::F_SCORES:
                    case self::F_PERIOD_M:

                        $values = [];

                        foreach ($factorValues as $factorValue) {
                            $this->definitions = $definitions = self::$decodeValues[$factorValue['f']];
                            $this->definitions['b'] = $definitions['b'] = $factorValue['b'];
                            $this->definitions['ctx'] = $definitions['ctx'] = $context;

                            $fValue = $factorValue['v'] ?? null;
                            $fPoint = $factorValue['pt'] ?? $definitions['point'] ?? null;
                            $fPoint = $this->_pointTypeHint($fPoint);

                            if (is_null($fValue)) {
                                continue;
                            }

                            $fValIndex = $definitions[3];
                            $fValName = $definitions['vn'];
                            $symbol = $definitions['symb'];
                            $other = [
                                1 => $definitions[4]['first'],
                                2 => $definitions[4]['second'],
                            ];

                            $id = $this->_genHash([$period, $fGroupCode, $fName, $fPoint, $fValIndex]);

                            $values[$fValIndex] = [
                                'v'     => $fValue,
                                'i'     => $fValIndex,
                                'id'    => $id,
                                'pt'    => $fPoint,
                                'vn'    => $fValName,
                                'symb'  => $symbol,
                                'other' => $other
                            ];
                        }

                        foreach ($values as $value) {
                            $this->calculateMargin1($eventId, [
                                'name'   => $fName,
                                'period' => $period,
                                'v1'     => $value['v'],
                                'i1'     => $value['i'],
                                'hash1'  => $value['id'],
                                'vn1'    => $value['vn'],
                                'pt1'    => null
                            ]);

                            if (isset($this->redisValues[$eventId][$value['id']])) {
                                $id = $value['id'];

                                $this->redisValues[$eventId][$id]['o'] = $value['other'];
                                $this->redisValues[$eventId][$id]['symb'] = $value['symb'];
                            }
                        }

                        break;
                }
            }
        }

        /**
         * Разбираем многострочные (фора, тоталы, инд. тоталы)
         */
        foreach ( $outcomeTails as $context => $periodsValues ) {
            foreach ( $periodsValues as $period => $fNames ) {
                foreach ( $fNames as $fName => $points ) {
                    /**
                     * Группируем по поинтам
                     */
                    $grouped = []; // тоталы, форы
                    $groupedTeam = []; // инд. тоталы

                    foreach ( $points as $point ) {
                        $definitions = $point['definitions'];

                        $fGroupCode = $definitions[1];
                        $fValIndex  = $point['i'];
                        $fPoint     = $point['pt'];

                        if( $fGroupCode === self::F_FORA && $fValIndex === self::V_SECOND ) {
                            $fPoint *= -1;
                        }

                        $fPoint = (string) $fPoint;

                        if( $fGroupCode === self::F_I_TOTAL ) {
                            $teamIndex = 0;

                            if( $fValIndex === self::V_FIRST_LOWER || $fValIndex === self::V_FIRST_UPPER ) {
                                $teamIndex = 1;
                            }

                            if( $fValIndex === self::V_SECOND_LOWER || $fValIndex === self::V_SECOND_UPPER ) {
                                $teamIndex = 2;
                            }

                            $groupedTeam[$teamIndex][$fPoint][] = $point;
                            continue;
                        }

                        $grouped[$fPoint][] = $point;
                    }

                    /**
                     * Собираем и обробатываем хвосты
                     */
                    $tails = [];
                    foreach( $grouped as $point => $values ) {
                        $tails[$point]['head']   = 0;
                        $tails[$point]['margin'] = 0;
                        $tails[$point]['pt']     = $point;
                        $tails[$point]['values'] = $values;
                        $tails[$point]['diff']   = $this->_diffValue($values);
                    }

                    if( !empty($tails) ) {
                        $tails = $this->_tailProcessing($tails);
                    }

                    foreach ( $groupedTeam as $teamIndex => $grouped ) {
                        $teamTails = [];

                        foreach ( $grouped as $point => $values ) {
                            $teamTails[$point]['head']   = 0;
                            $teamTails[$point]['margin'] = 0;
                            $teamTails[$point]['pt']     = $point;
                            $teamTails[$point]['values'] = $values;
                            $teamTails[$point]['diff']   = $this->_diffValue($values);
                        }

                        $tails = array_merge($tails, $this->_tailProcessing($teamTails));
                    }

                    foreach ( $tails as $tail ) {
                        $head = $tail['head'];

                        if( $head && ($fName !== 7 && $fName !== 8) ) {
                            $head = 0;
                        }

                        $values = $tail['values'];
                        $addendumMargin = $tail['margin'];

                        $definitions1 = $values[0]['definitions'] ?? null;
                        $definitions2 = $values[1]['definitions'] ?? null;

                        /**
                         * Переопределяем definitions так как многострочные обробатываем последними
                         * и на этот момен definitions не валидный
                         */
                        $this->definitions = $definitions1 ?? $definitions2 ?? [];

                        $id1 = $values[0]['id'] ?? null;
                        $id2 = $values[1]['id'] ?? null;

                        $this->calculateMargin2($eventId, [
                            'name'   => $fName,
                            'period' => $period,
                            'v1'     => $values[0]['v'] ?? null,
                            'v2'     => $values[1]['v'] ?? null,
                            'i1'     => $values[0]['i'] ?? null,
                            'i2'     => $values[1]['i'] ?? null,
                            'hash1'  => $id1,
                            'hash2'  => $id2,
                            'vn1'    => $definitions1['vn'] ?? null,
                            'vn2'    => $definitions2['vn'] ?? null,
                            'pt1'    => $values[0]['pt'] ?? null,
                            'pt2'    => $values[1]['pt'] ?? null,
                        ]);

                        if (isset($this->redisValues[$eventId][$id1])) {
                            $this->redisValues[$eventId][$id1]['h'] = $head;
                        }

                        if (isset($this->redisValues[$eventId][$id2])) {
                            $this->redisValues[$eventId][$id2]['h'] = $head;
                        }
                    }
                }
            }
        }
    }

    private function _diffValue($values)
    {
        $v1 = 0;
        $v2 = 0;

        if( !empty($values[0]) ) {
            $v1 = $values[0]['v'];
        }

        if( !empty($values[1]) ) {
            $v2 = $values[1]['v'];
        }

        return abs($v1 - $v2);
    }

    private function _tailProcessing($tails)
    {
	    $indexPt = null;
	    $minDiff = null;

	    foreach ( $tails as $pt => $tail ) {
	        if( is_null($minDiff) ) {
                $indexPt = $pt;
                $minDiff = $tail['diff'];
            }

	        if( $tail['diff'] < $minDiff ) {
                $indexPt = $pt;
                $minDiff = $tail['diff'];
            }
        }

        /**
         * Поинт с минимальным дифом помечаем как заголовочный
         */
        $tails[$indexPt]['head'] = 1;

        $pivotPoint = $tails[$indexPt];
        unset($tails[$indexPt]);

	    $morePoints = [];
	    $lessPoints = [];

	    foreach ( $tails as $pt => $point ) {
	        if( $pt > $indexPt ) {
                $morePoints[] = $point;
            }

	        if( $pt < $indexPt ) {
                $lessPoints[] = $point;
            }
        }

        usort($morePoints, function($v1, $v2) {
            $pt1 = abs($v1['pt']);
            $pt2 = abs($v2['pt']);

            if( $pt1 === $pt2 ) {
                return 0;
            }

            return $pt1 < $pt2 ? -1 : 1;
        });
        usort($lessPoints, function($v1, $v2) {
            $pt1 = abs($v1['pt']);
            $pt2 = abs($v2['pt']);

            if( $pt1 === $pt2 ) {
                return 0;
            }

            return $pt1 < $pt2 ? -1 : 1;
        });

        $counter = max(count($morePoints), count($lessPoints));

//        $margin = $this->_margin->getForMarket($pivotPoint['values'][0]['definitions'][2], Margin::MODE_DEFAULT);
        $marginStep = self::TAIL_MARGIN_STEP;

	    for($i = 0; $i < $counter; $i++) {
//	        if( ($margin + $marginStep) > self::TAIL_MARGIN_MAX ) {
//                $marginStep = ($margin + $marginStep) - self::TAIL_MARGIN_MAX;
//            }

	        if( isset($morePoints[$i]) ) {
                $morePoints[$i]['margin'] = $marginStep;
            }

	        if( isset($lessPoints[$i]) ) {
                $lessPoints[$i]['margin'] = $marginStep;
            }

            $marginStep += self::TAIL_MARGIN_STEP;
        }

        return array_merge($morePoints, [$pivotPoint], $lessPoints);
    }

    private function _pointTypeHint($pt)
    {
        if( is_null($pt) ) {
            return null;
        }

        if( strpos($pt, '.') !== false ) {
            return (float) $pt;
        }

        return (int) $pt;
    }
    
	private function calculateMargin1($eventId, $data): bool
	{
        $name           = $data['name'];
        $v1             = $data['v1'];
        $index1         = $data['i1'];
        $id1            = $data['hash1'];
        $vn1            = $data['vn1'];
        $pt1            = $data['pt1'];
        $period         = $data['period'];
        $is3outcomes    = $data['is3outcomes'] ?? false;
        $addendumMargin = $data['addendumMargin'] ?? 0;

		if ( $v1 < 1 ) {
			return false;
		}

		$divider = ($is3outcomes ? 3 : 2);

        /**
         * Вычисляем чистые кэфы без маржи
         */
        $value1Percent = 100 / $v1;
        $value2Percent = 100 - $value1Percent;

        $marginPercent = $value1Percent + $value2Percent - 100;
        $value1ClearPercent = $value1Percent - $marginPercent / $divider;

        /**
         * Высчитываем маржу для default, high, low
         */
        if( $this->_margin->getMarginMode() ) {
            $margin     = $this->_margin->getForMarket($name, Margin::MODE_DEFAULT);
            $marginHigh = $this->_margin->getForMarket($name, Margin::MODE_HIGH);
            $marginLow  = $this->_margin->getForMarket($name, Margin::MODE_LOW);
        } else {
            $margin     = $marginPercent;
            $marginHigh = $marginPercent + $this->_margin->getMarginStep();
            $marginLow  = $marginPercent - $this->_margin->getMarginStep();
        }

        $margin     = $this->_margin->checkMargin($margin + $addendumMargin);
        $marginHigh = $this->_margin->checkMargin($marginHigh + $addendumMargin);
        $marginLow  = $this->_margin->checkMargin($marginLow + $addendumMargin);

        $marginValueDefault = $margin / $divider;
        $marginValueHigh    = $marginLow / $divider;
        $marginValueLow     = $marginHigh / $divider;

        /**
         * Вычесляем значение кэфов с учётом нашей маржи
         */
        $value1clear = max(1.01, round(100 / $value1ClearPercent, 2, PHP_ROUND_HALF_DOWN));
        $value1Default = max(1.01, round(100 / ($value1ClearPercent + $marginValueDefault), 2, PHP_ROUND_HALF_DOWN));
        $value1High = max(1.01, round(100 / ($value1ClearPercent + $marginValueHigh), 2, PHP_ROUND_HALF_DOWN));
        $value1Low = max(1.01, round(100 / ($value1ClearPercent + $marginValueLow), 2, PHP_ROUND_HALF_DOWN));

        $this->recValue($eventId, $id1, $value1clear, $value1Default, $value1High, $value1Low, $index1, $vn1, $pt1, $name, $period);

		return true;
	}

	private function calculateMargin2($eventId, $data): bool
	{
        $name           = $data['name'];
        $v1             = $data['v1'];
        $v2             = $data['v2'];
        $index1         = $data['i1'];
        $index2         = $data['i2'];
        $id1            = $data['hash1'];
        $id2            = $data['hash2'];
        $vn1            = $data['vn1'];
        $vn2            = $data['vn2'];
        $pt1            = $data['pt1'];
        $pt2            = $data['pt2'];
        $period         = $data['period'];
        $is3outcomes    = $data['is3outcomes'] ?? false;
        $addendumMargin = $data['addendumMargin'] ?? 0;

        if ( $v1 < 1 || $v2 < 1 ) {
            if ( $v1 > 1 ) {
                $this->calculateMargin1($eventId, [
                    'name'           => $name,
                    'v1'             => $v1,
                    'i1'             => $index1,
                    'hash1'          => $id1,
                    'vn1'            => $vn1,
                    'pt1'            => $pt1,
                    'period'         => $period,
                    'is3outcomes'    => $is3outcomes,
                    'addendumMargin' => $addendumMargin
                ]);
            }

            if ( $v2 > 1 ) {
                $this->calculateMargin1($eventId, [
                    'name'           => $name,
                    'v1'             => $v2,
                    'i1'             => $index2,
                    'hash1'          => $id2,
                    'vn1'            => $vn2,
                    'pt1'            => $pt2,
                    'period'         => $period,
                    'is3outcomes'    => $is3outcomes,
                    'addendumMargin' => $addendumMargin
                ]);
            }

            return false;
        }

        /**
         * Вычисляем чистые кэфы без маржи
         */
        $value1Percent = 100 / $v1;
        $value2Percent = 100 / $v2;

        $marginPercent = $value1Percent + $value2Percent - 100;

        $value1ClearPercent = $value1Percent - $marginPercent / 2;
        $value2ClearPercent = $value2Percent - $marginPercent / 2;

        /**
         * Высчитываем маржу для default, high, low
         */
        if( $this->_margin->getMarginMode() ) {
            $margin     = $this->_margin->getForMarket($name, Margin::MODE_DEFAULT);
            $marginHigh = $this->_margin->getForMarket($name, Margin::MODE_HIGH);
            $marginLow  = $this->_margin->getForMarket($name, Margin::MODE_LOW);
        } else {
            $margin     = $marginPercent;
            $marginHigh = $marginPercent + $this->_margin->getMarginStep();
            $marginLow  = $marginPercent - $this->_margin->getMarginStep();
        }

        $margin     = $this->_margin->checkMargin($margin + $addendumMargin);
        $marginHigh = $this->_margin->checkMargin($marginHigh + $addendumMargin);
        $marginLow  = $this->_margin->checkMargin($marginLow + $addendumMargin);

        $marginValueDefault = $margin / 2;
        $marginValueHigh    = $marginLow / 2;
        $marginValueLow     = $marginHigh / 2;

        /**
         * Вычесляем значение кэфов с учётом нашей маржи
         */
        $value1Clear = max(1.01, round(100 / $value1ClearPercent, 2, PHP_ROUND_HALF_DOWN));
        $value2Clear = max(1.01, round(100 / $value2ClearPercent, 2, PHP_ROUND_HALF_DOWN));

        $value1Default = max(1.01, round(100 / ($value1ClearPercent + $marginValueDefault), 2, PHP_ROUND_HALF_DOWN));
		$value2Default = max(1.01, round(100 / ($value2ClearPercent + $marginValueDefault), 2, PHP_ROUND_HALF_DOWN));

		$value1High = max(1.01, round(100 / ($value1ClearPercent + $marginValueHigh), 2, PHP_ROUND_HALF_DOWN));
		$value2High = max(1.01, round(100 / ($value2ClearPercent + $marginValueHigh), 2, PHP_ROUND_HALF_DOWN));

        $value1Low = max(1.01, round(100 / ($value1ClearPercent + $marginValueLow), 2, PHP_ROUND_HALF_DOWN));
        $value2Low = max(1.01, round(100 / ($value2ClearPercent + $marginValueLow), 2, PHP_ROUND_HALF_DOWN));

		$this->recValue($eventId, $id1, $value1Clear, $value1Default, $value1High, $value1Low, $index1, $vn1, $pt1, $name, $period);
		$this->recValue($eventId, $id2, $value2Clear, $value2Default, $value2High, $value2Low, $index2, $vn2, $pt2, $name, $period);

		return true;
	}

	private function calculateMargin3($eventId, $data): bool
	{
        $name   = $data['name'];
        $v1     = $data['v1'];
        $v2     = $data['v2'];
        $v3     = $data['v3'];
        $index1 = $data['i1'];
        $index2 = $data['i2'];
        $index3 = $data['i3'];
        $id1    = $data['hash1'];
        $id2    = $data['hash2'];
        $id3    = $data['hash3'];
        $vn1    = $data['vn1'];
        $vn2    = $data['vn2'];
        $vn3    = $data['vn3'];
        $pt1    = $data['pt1'];
        $pt2    = $data['pt2'];
        $pt3    = $data['pt3'];
        $period = $data['period'];

		if ( $v1 < 1 || $v2 < 1 || $v3 < 1 ) {
            if( $v1 > 1 && $v2 > 1 ) {
                return $this->calculateMargin2($eventId, [
                    'name'        => $name,
                    'v1'          => $v1,
                    'v2'          => $v2,
                    'i1'          => $index1,
                    'i2'          => $index2,
                    'hash1'       => $id1,
                    'hash2'       => $id2,
                    'vn1'         => $vn1,
                    'vn2'         => $vn2,
                    'pt1'         => $pt1,
                    'pt2'         => $pt2,
                    'period'      => $period,
                    'is3outcomes' => true
                ]);
            }

            if( $v2 > 1 && $v3 > 1 ) {
                return $this->calculateMargin2($eventId, [
                    'name'        => $name,
                    'v1'          => $v2,
                    'v2'          => $v3,
                    'i1'          => $index2,
                    'i2'          => $index3,
                    'hash1'       => $id2,
                    'hash2'       => $id3,
                    'vn1'         => $vn2,
                    'vn2'         => $vn3,
                    'pt1'         => $pt2,
                    'pt2'         => $pt3,
                    'period'      => $period,
                    'is3outcomes' => true
                ]);
            }

            if( $v3 > 1 && $v1 > 1 ) {
                return $this->calculateMargin2($eventId, [
                    'name'        => $name,
                    'v1'          => $v3,
                    'v2'          => $v1,
                    'i1'          => $index3,
                    'i2'          => $index1,
                    'hash1'       => $id3,
                    'hash2'       => $id1,
                    'vn1'         => $vn3,
                    'vn2'         => $vn1,
                    'pt1'         => $pt3,
                    'pt2'         => $pt1,
                    'period'      => $period,
                    'is3outcomes' => true
                ]);
            }

            if ( $v1 > 1 ) {
                return $this->calculateMargin1($eventId, [
                    'name'        => $name,
                    'v1'          => $v1,
                    'i1'          => $index1,
                    'hash1'       => $id1,
                    'vn1'         => $vn1,
                    'pt1'         => $pt1,
                    'period'      => $period,
                    'is3outcomes' => true
                ]);
            }

            if ( $v2 > 1 ) {
                return $this->calculateMargin1($eventId, [
                    'name'        => $name,
                    'v1'          => $v2,
                    'i1'          => $index2,
                    'hash1'       => $id2,
                    'vn1'         => $vn2,
                    'pt1'         => $pt2,
                    'period'      => $period,
                    'is3outcomes' => true
                ]);
            }

            if ( $v3 > 1 ) {
                return $this->calculateMargin1($eventId, [
                    'name'        => $name,
                    'v1'          => $v3,
                    'i1'          => $index3,
                    'hash1'       => $id3,
                    'vn1'         => $vn3,
                    'pt1'         => $pt3,
                    'period'      => $period,
                    'is3outcomes' => true
                ]);
            }

			return false;
		}

        /**
         * Вычисляем чистые кэфы без маржи
         */
        $value1Percent = 100 / $v1;
        $value2Percent = 100 / $v2;
        $value3Percent = 100 / $v3;

        $marginPercent = $value1Percent + $value2Percent + $value3Percent - 100;

        $value1ClearPercent = $value1Percent - $marginPercent / 3;
        $value2ClearPercent = $value2Percent - $marginPercent / 3;
        $value3ClearPercent = $value3Percent - $marginPercent / 3;

        /**
         * Высчитываем маржу для default, high, low
         */
        if( $this->_margin->getMarginMode() ) {
            $margin     = $this->_margin->getForMarket($name, Margin::MODE_DEFAULT);
            $marginHigh = $this->_margin->getForMarket($name, Margin::MODE_HIGH);
            $marginLow  = $this->_margin->getForMarket($name, Margin::MODE_LOW);
        } else {
            $margin     = $marginPercent;
            $marginHigh = $marginPercent + $this->_margin->getMarginStep();
            $marginLow  = $marginPercent - $this->_margin->getMarginStep();
        }

        $margin     = $this->_margin->checkMargin($margin);
        $marginHigh = $this->_margin->checkMargin($marginHigh);
        $marginLow  = $this->_margin->checkMargin($marginLow);

        $marginValueDefault = $margin / 3;
        $marginValueHigh    = $marginLow / 3;
        $marginValueLow     = $marginHigh / 3;

        /**
         * Вычесляем значение кэфов с учётом нашей маржи
         */
        $value1Clear = max(1.01, round(100 / $value1ClearPercent, 2, PHP_ROUND_HALF_DOWN));
        $value2Clear = max(1.01, round(100 / $value2ClearPercent, 2, PHP_ROUND_HALF_DOWN));
        $value3Clear = max(1.01, round(100 / $value3ClearPercent, 2, PHP_ROUND_HALF_DOWN));

        $value1Default = max(1.01, round(100 / ($value1ClearPercent + $marginValueDefault), 2, PHP_ROUND_HALF_DOWN));
		$value2Default = max(1.01, round(100 / ($value2ClearPercent + $marginValueDefault), 2, PHP_ROUND_HALF_DOWN));
		$value3Default = max(1.01, round(100 / ($value3ClearPercent + $marginValueDefault), 2, PHP_ROUND_HALF_DOWN));

		$value1High = max(1.01, round(100 / ($value1ClearPercent + $marginValueHigh), 2, PHP_ROUND_HALF_DOWN));
		$value2High = max(1.01, round(100 / ($value2ClearPercent + $marginValueHigh), 2, PHP_ROUND_HALF_DOWN));
		$value3High = max(1.01, round(100 / ($value3ClearPercent + $marginValueHigh), 2, PHP_ROUND_HALF_DOWN));

        $value1Low = max(1.01, round(100 / ($value1ClearPercent + $marginValueLow), 2, PHP_ROUND_HALF_DOWN));
        $value2Low = max(1.01, round(100 / ($value2ClearPercent + $marginValueLow), 2, PHP_ROUND_HALF_DOWN));
        $value3Low = max(1.01, round(100 / ($value3ClearPercent + $marginValueLow), 2, PHP_ROUND_HALF_DOWN));

		$this->recValue($eventId, $id1, $value1Clear, $value1Default, $value1High, $value1Low, $index1, $vn1, $pt1, $name, $period);
		$this->recValue($eventId, $id2, $value2Clear, $value2Default, $value2High, $value2Low, $index2, $vn2, $pt2, $name, $period);
		$this->recValue($eventId, $id3, $value3Clear, $value3Default, $value3High, $value3Low, $index3, $vn3, $pt3, $name, $period);

		return true;
	}

    /**
     * @param $eventId    - текущее обробатываемое событие
     * @param $factorId   - id коэффициента
     * @param $valueClear - значение коэффициента 
     * @param $value      - значение коэффициента
     * @param $valueHigh  - значение коэффициента
     * @param $valueLow   - значение коэффициента
     * @param $fValIndex - индекс значения коэффициента
     * @param $vn        - имя значения коэффициента
     * @param $pt        - поинт для коэффициента
     * @param $name      - название коэффициента
     * @param $period    - период
     */
    private function recValue($eventId, $factorId, $valueClear, $value, $valueHigh, $valueLow, $fValIndex, $vn, $pt, $name, $period)
    {
        $type = $this->definitions[1];
//        $hashes = $this->_generateOppositeHashes($period, $type, $name, $pt, $fValIndex);
        
        $this->redisValues[$eventId][$factorId]['f']    = $factorId; //Хэш коэффициента
        $this->redisValues[$eventId][$factorId]['h']    = $this->definitions[0] == self::HEAD ? 1 : 0;
        $this->redisValues[$eventId][$factorId]['t']    = $type; //Тип коэффициента
        $this->redisValues[$eventId][$factorId]['n']    = $name; //Имя коэффициента
        $this->redisValues[$eventId][$factorId]['p']    = $period;    // Период (0 - матч)
        $this->redisValues[$eventId][$factorId]['vn']   = $vn;        //Название значения
        $this->redisValues[$eventId][$factorId]['i']    = $fValIndex; //Индекс значения
        $this->redisValues[$eventId][$factorId]['vc']   = $valueClear;//Значение (без маржи)
        $this->redisValues[$eventId][$factorId]['v']    = $value;     //Значение (маржа по умолчанию)
        $this->redisValues[$eventId][$factorId]['vh']   = $valueHigh; //Значение (пониженная маржа)
        $this->redisValues[$eventId][$factorId]['vl']   = $valueLow;  //Значение (повышенная маржа)
        $this->redisValues[$eventId][$factorId]['b']    = $this->definitions['b']; //Заблокирован кэф или нет
        $this->redisValues[$eventId][$factorId]['ctx']  = $this->definitions['ctx']; //Контекст для кэфа
        $this->redisValues[$eventId][$factorId]['pt']   = $pt; //Поинт коэффициента
        $this->redisValues[$eventId][$factorId]['symb'] = null; //Символ коэффициента
    }

    private function _generateOppositeHashes($period, $fGroupCode, $fName, $fPoint, $fValIndex)
    {
        $oppositeValues = self::$_doublePairValIndex[$fValIndex] ?? [];

        if( array_search($fGroupCode, [self::F_OUTCOMES, self::F_DBL_CHANCE, self::F_ETEAM, self::F_EFFECTIVE]) !== false ) {
            $oppositeValues = self::$_triplePairValIndex[$fValIndex] ?? [];
        }

        if( empty($oppositeValues) ) {
            return [];
        }

        $out = [];

        foreach ( $oppositeValues as $oppositeValIndex ) {
            if( $fGroupCode === self::F_FORA ) {
                $fPoint *= -1;
            }

            $out[] = $this->_genHash([$period, $fGroupCode, $fName, $fPoint, $oppositeValIndex]);
        }

        return $out;
    }

    private function _genHash(array $ar)
    {
        return substr(md5(implode('.', $ar)), 0, 8);
    }
}

/**
 * Чтобы выключить парсинг некоторых коэффициентов
 * то комментить надо тут
 */
Factors::$decodeValues = [
//    this.app
//    this._event.manager.app.catalogManager
//    .catalog._gridObjectsByFactorId

    /**
     * Исходы
     */
	921  => [Factors::HEAD, Factors::F_OUTCOMES, 1, Factors::V_FIRST, 'vn' => 3],
	922  => [Factors::HEAD, Factors::F_OUTCOMES, 1, Factors::V_DRAW, 'vn' => 4],
	923  => [Factors::HEAD, Factors::F_OUTCOMES, 1, Factors::V_SECOND, 'vn' => 5],

    3144  => [Factors::HEAD, Factors::F_OUTCOMES, 1, Factors::V_FIRST, 'vn' => 3],
    3145  => [Factors::HEAD, Factors::F_OUTCOMES, 1, Factors::V_SECOND, 'vn' => 5],

    /*
     * Двойной шанс
     */
	924  => [Factors::HEAD, Factors::F_DBL_CHANCE, 2, Factors::V_FIRST, 'vn' => 6],
	1571 => [Factors::HEAD, Factors::F_DBL_CHANCE, 2, Factors::V_DRAW, 'vn' => 7],
	925  => [Factors::HEAD, Factors::F_DBL_CHANCE, 2, Factors::V_SECOND, 'vn' => 8],

    /**
     * Фора внешняя
     */
	927  => [Factors::HEAD, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	928  => [Factors::HEAD, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],

	937 => [Factors::HEAD, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	938 => [Factors::HEAD, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],

	1845 => [Factors::HEAD, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	1846 => [Factors::HEAD, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],

    /**
     * Азиатская фора
     */
//    983  => ['t' => Factor::F_FORA, 'n' => 388, 'i' => Factor::V_FIRST],
//    984  => ['t' => Factor::F_FORA, 'n' => 388, 'i' => Factor::V_SECOND],


    //тотал внешний
	930  => [Factors::HEAD, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	931  => [Factors::HEAD, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],

	940 => [Factors::HEAD, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	941 => [Factors::HEAD, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],

	1848 => [Factors::HEAD, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1849 => [Factors::HEAD, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],

    /**
     * Азиатский тотал
     */
//    986  => ['t' => Factor::F_TOTAL, 'n' => 389, 'i' => Factor::V_UPPER],
//    987  => ['t' => Factor::F_TOTAL, 'n' => 389, 'i' => Factor::V_LOWER],

	/**
	 * форы внутри
	 */
	910  => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	989  => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	1569 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	1672 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	1677 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	1680 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	1683 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	1686 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	1689 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	1692 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],
	1723 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_FIRST, 'vn' => 9],

	912  => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],
	991  => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],
	1572 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],
	1675 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],
	1678 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],
	1681 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],
	1684 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],
	1687 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],
	1690 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],
	1718 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],
	1724 => [Factors::BODY, Factors::F_FORA, 7, Factors::V_SECOND, 'vn' => 10],

//    2422 => [Factors::BODY, Factors::F_FORA, 25, Factors::V_FIRST, 'vn' => 9],
//    2421 => [Factors::BODY, Factors::F_FORA, 25, Factors::V_SECOND, 'vn' => 10],

	2424 => [Factors::BODY, Factors::F_FORA, 25, Factors::V_FIRST, 'vn' => 9],
	2425 => [Factors::BODY, Factors::F_FORA, 25, Factors::V_SECOND, 'vn' => 10],

	2427 => [Factors::BODY, Factors::F_FORA, 25, Factors::V_FIRST, 'vn' => 9],
	2428 => [Factors::BODY, Factors::F_FORA, 25, Factors::V_SECOND, 'vn' => 10],

//    2430 => ['t' => Factor::F_FORA, 'n' => 25, 'i' => Factor::V_FIRST],
//    2431 => ['t' => Factor::F_FORA, 'n' => 25, 'i' => Factor::V_SECOND],

//    2433 => ['t' => Factor::F_FORA, 'n' => 25, 'i' => Factor::V_FIRST],
//    2434 => ['t' => Factor::F_FORA, 'n' => 25, 'i' => Factor::V_SECOND],

	2436 => [Factors::BODY, Factors::F_FORA, 25, Factors::V_FIRST, 'vn' => 9],
	2437 => [Factors::BODY, Factors::F_FORA, 25, Factors::V_SECOND, 'vn' => 10],

	/**
	 * Тоталы внутри
	 */
	1696 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1727 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1730 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1733 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1736 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1739 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1793 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1796 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1799 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1802 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],
	1805 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_UPPER, 'vn' => 11],

	1697 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],
	1728 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],
	1731 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],
	1734 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],
	1737 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],
	1791 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],
	1794 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],
	1797 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],
	1800 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],
	1803 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],
	1806 => [Factors::BODY, Factors::F_TOTAL, 8, Factors::V_LOWER, 'vn' => 12],

//	1353 => [Factors::BODY, Factors::F_TOTAL, 87, Factors::V_UPPER, 'vn' => 11],
//	1354 => [Factors::BODY, Factors::F_TOTAL, 87, Factors::V_LOWER, 'vn' => 12],

//	1356 => [Factors::BODY, Factors::F_TOTAL, 88, Factors::V_UPPER, 'vn' => 11],
//	1357 => [Factors::BODY, Factors::F_TOTAL, 88, Factors::V_LOWER, 'vn' => 12],

//	1429 => [Factors::BODY, Factors::F_TOTAL, 42, Factors::V_UPPER, 'vn' => 11],
//	1430 => [Factors::BODY, Factors::F_TOTAL, 42, Factors::V_LOWER, 'vn' => 12],

	/*
	 * тайбрейки
	 */
//	671  => [Factors::BODY, Factors::F_TOTAL, 67, Factors::V_UPPER, 'vn' => 11],
//	673  => [Factors::BODY, Factors::F_TOTAL, 67, Factors::V_LOWER, 'vn' => 12],

    /*
     * Кол-во сетов
     */
    917 => [Factors::BODY, Factors::F_TOTAL, 68, Factors::V_UPPER, 'vn' => 11],
    918 => [Factors::BODY, Factors::F_TOTAL, 68, Factors::V_LOWER, 'vn' => 12],

    /**
     * Тотал самого результативного сета
     */
//    2405 => [Factors::BODY, Factors::F_TOTAL, 65, Factors::V_LOWER, 'vn' => 12],
//    2406 => [Factors::BODY, Factors::F_TOTAL, 65, Factors::V_UPPER, 'vn' => 11],

    /**
     * Тотал самого НЕрезультативного сета
     */
//    2402 => [Factors::BODY, Factors::F_TOTAL, 66, Factors::V_LOWER, 'vn' => 12],
//    2403 => [Factors::BODY, Factors::F_TOTAL, 66, Factors::V_UPPER, 'vn' => 11],

    /**
     * Тотал самой результативной карты
     */
//    3271 => [Factors::BODY, Factors::F_TOTAL, 65, Factors::V_LOWER, 'vn' => 12],
//    3272 => [Factors::BODY, Factors::F_TOTAL, 65, Factors::V_UPPER, 'vn' => 11],

    /**
     * Тотал самой НЕрезультативной карты
     */
//    3268 => [Factors::BODY, Factors::F_TOTAL, 66, Factors::V_LOWER, 'vn' => 12],
//    3269 => [Factors::BODY, Factors::F_TOTAL, 66, Factors::V_UPPER, 'vn' => 11],

    /**
     * Тотал самого результативного тайма
     */
//    5379 => [Factors::BODY, Factors::F_TOTAL, 65, Factors::V_LOWER, 'vn' => 12],
//    5380 => [Factors::BODY, Factors::F_TOTAL, 65, Factors::V_UPPER, 'vn' => 11],

    /**
     * Тотал самого НЕрезультативного тайма
     */
//    5376 => [Factors::BODY, Factors::F_TOTAL, 66, Factors::V_LOWER, 'vn' => 12],
//    5377 => [Factors::BODY, Factors::F_TOTAL, 66, Factors::V_UPPER, 'vn' => 11],

    /**
     * Тотал самого результативного периода
     */
    5385 => [Factors::BODY, Factors::F_TOTAL, 65, Factors::V_LOWER, 'vn' => 12],
    5386 => [Factors::BODY, Factors::F_TOTAL, 65, Factors::V_UPPER, 'vn' => 11],

    /**
     * Тотал самого НЕрезультативного периода
     */
    5382 => [Factors::BODY, Factors::F_TOTAL, 66, Factors::V_LOWER, 'vn' => 12],
    5383 => [Factors::BODY, Factors::F_TOTAL, 66, Factors::V_UPPER, 'vn' => 11],


    /**
     * Тотал самой результативной четверти
     */
//    7044 => [Factors::BODY, Factors::F_TOTAL, 66, Factors::V_LOWER, 'vn' => 12],
//    7045 => [Factors::BODY, Factors::F_TOTAL, 66, Factors::V_UPPER, 'vn' => 11],

    /**
     * Тотал самой НЕрезультативной четверти
     */
//    7047 => [Factors::BODY, Factors::F_TOTAL, 65, Factors::V_LOWER, 'vn' => 12],
//    7048 => [Factors::BODY, Factors::F_TOTAL, 65, Factors::V_UPPER, 'vn' => 11],


	/**
	 * Индивидуальные тоталы
	 */

	/**
	 * Первый
	 */
	1809 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_UPPER, 'vn' => 13],
	1810 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_LOWER, 'vn' => 14],

	1812 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_UPPER, 'vn' => 13],
	1813 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_LOWER, 'vn' => 14],

	1815 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_UPPER, 'vn' => 13],
	1816 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_LOWER, 'vn' => 14],

	1818 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_UPPER, 'vn' => 13],
	1819 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_LOWER, 'vn' => 14],

	1821 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_UPPER, 'vn' => 13],
	1822 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_LOWER, 'vn' => 14],

	1824 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_UPPER, 'vn' => 13],
	1825 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_LOWER, 'vn' => 14],

	1827 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_UPPER, 'vn' => 13],
	1828 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_LOWER, 'vn' => 14],

	1830 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_UPPER, 'vn' => 13],
	1831 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_LOWER, 'vn' => 14],

	2203 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_UPPER, 'vn' => 13],
	2204 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_LOWER, 'vn' => 14],

	/**
	 * Второй
	 */
	1854 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_UPPER, 'vn' => 15],
	1871 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_LOWER, 'vn' => 16],

	1873 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_UPPER, 'vn' => 15],
	1874 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_LOWER, 'vn' => 16],

	1880 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_UPPER, 'vn' => 15],
	1881 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_LOWER, 'vn' => 16],

	1883 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_UPPER, 'vn' => 15],
	1884 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_LOWER, 'vn' => 16],

	1886 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_UPPER, 'vn' => 15],
	1887 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_LOWER, 'vn' => 16],

	1893 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_UPPER, 'vn' => 15],
	1894 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_LOWER, 'vn' => 16],

	1896 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_UPPER, 'vn' => 15],
	1897 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_LOWER, 'vn' => 16],

	1899 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_UPPER, 'vn' => 15],
	1900 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_LOWER, 'vn' => 16],

	2209 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_UPPER, 'vn' => 15],
	2210 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_LOWER, 'vn' => 16],

	/**
	 * В табличке на фонбете
	 */
	974 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_UPPER, 'vn' => 13],
	976 => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_FIRST_LOWER, 'vn' => 14],

	978  => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_UPPER, 'vn' => 15],
	980  => [Factors::BODY, Factors::F_I_TOTAL, 9, Factors::V_SECOND_LOWER, 'vn' => 16],

    /**
     * Ничья и тотал > %pt
     */
    1099 => [Factors::BODY, Factors::F_BOOL, 321, Factors::V_YES, 'vn' => 20],
    1100 => [Factors::BODY, Factors::F_BOOL, 321, Factors::V_NO, 'vn' => 21],

    /**
     * Ничья и тотал < %pt
     */
    1097 => [Factors::BODY, Factors::F_BOOL, 320, Factors::V_YES, 'vn' => 20],
    1098 => [Factors::BODY, Factors::F_BOOL, 320, Factors::V_NO, 'vn' => 21],

    /**
     * Будет пенальти
     */
    689  => [Factors::BODY, Factors::F_BOOL, 3, Factors::V_YES, 'vn' => 20],
    690  => [Factors::BODY, Factors::F_BOOL, 3, Factors::V_NO, 'vn' => 21],

    /**
     * Будет овертайм
     */
    3158 => [Factors::BODY, Factors::F_BOOL, 224, Factors::V_YES, 'vn' => 20],
    3159 => [Factors::BODY, Factors::F_BOOL, 224, Factors::V_NO, 'vn' => 21],

    /**
     * Будет удаление
     */
    692  => [Factors::BODY, Factors::F_BOOL, 4, Factors::V_YES, 'vn' => 20],
    693  => [Factors::BODY, Factors::F_BOOL, 4, Factors::V_NO, 'vn' => 21],

    /**
     * Обе забьют
     */
    4241  => [Factors::BODY, Factors::F_BOOL, 69, Factors::V_YES, 'vn' => 20],
    4242  => [Factors::BODY, Factors::F_BOOL, 69, Factors::V_NO, 'vn' => 21],

    /**
     * Обе забьют и ничья
     */
    4835  => [Factors::BODY, Factors::F_BOOL, 358, Factors::V_YES, 'vn' => 20],
    4836  => [Factors::BODY, Factors::F_BOOL, 358, Factors::V_NO, 'vn' => 21],

    /**
     * Обе забьют и нет ничьей
     */
    4844  => [Factors::BODY, Factors::F_BOOL, 360, Factors::V_YES, 'vn' => 20],
    4845  => [Factors::BODY, Factors::F_BOOL, 360, Factors::V_NO, 'vn' => 21],

    /**
     * Хотя бы одна не забьет и нет ничьей
     */
    4869  => [Factors::BODY, Factors::F_BOOL, 367, Factors::V_YES, 'vn' => 20],
    4870  => [Factors::BODY, Factors::F_BOOL, 367, Factors::V_NO, 'vn' => 21],

    /**
     * Обе забьют и общий тотал > %pt
     */
    4850 => [Factors::BODY, Factors::F_BOOL, 361, Factors::V_YES, 'vn' => 20],
    4851 => [Factors::BODY, Factors::F_BOOL, 361, Factors::V_NO, 'vn' => 21],

    4853 => [Factors::BODY, Factors::F_BOOL, 361, Factors::V_YES, 'vn' => 20],
    4854 => [Factors::BODY, Factors::F_BOOL, 361, Factors::V_NO, 'vn' => 21],

    /**
     * Обе забьют и общий тотал < %pt
     */
    5065 => [Factors::BODY, Factors::F_BOOL, 362, Factors::V_YES, 'vn' => 20],
    5066 => [Factors::BODY, Factors::F_BOOL, 362, Factors::V_NO, 'vn' => 21],

    5068 => [Factors::BODY, Factors::F_BOOL, 362, Factors::V_YES, 'vn' => 20],
    5069 => [Factors::BODY, Factors::F_BOOL, 362, Factors::V_NO, 'vn' => 21],

    /**
     * Только одна забьет
     */
    4250  => [Factors::BODY, Factors::F_BOOL, 356, Factors::V_YES, 'vn' => 20],
    4251  => [Factors::BODY, Factors::F_BOOL, 356, Factors::V_NO, 'vn' => 21],

    /**
     * Никто не забьет
     */
    4253  => [Factors::BODY, Factors::F_BOOL, 357, Factors::V_YES, 'vn' => 20],
    4254  => [Factors::BODY, Factors::F_BOOL, 357, Factors::V_NO, 'vn' => 21],

    /**
     * Хотя бы одна не забьет и ничья
     */
    4860  => [Factors::BODY, Factors::F_BOOL, 365, Factors::V_YES, 'vn' => 20],
    4861  => [Factors::BODY, Factors::F_BOOL, 365, Factors::V_NO, 'vn' => 21],

    /**
     * Оба тайма < %pt
     */
	1085 => [Factors::BODY, Factors::F_BOOL, 91, Factors::V_YES, 'vn' => 20],
	1086 => [Factors::BODY, Factors::F_BOOL, 91, Factors::V_NO, 'vn' => 21],

    5143 => [Factors::BODY, Factors::F_BOOL, 91, Factors::V_YES, 'vn' => 20],
    5144 => [Factors::BODY, Factors::F_BOOL, 91, Factors::V_NO, 'vn' => 21],

    5146 => [Factors::BODY, Factors::F_BOOL, 91, Factors::V_YES, 'vn' => 20],
    5147 => [Factors::BODY, Factors::F_BOOL, 91, Factors::V_NO, 'vn' => 21],

    5149 => [Factors::BODY, Factors::F_BOOL, 91, Factors::V_YES, 'vn' => 20],
    5150 => [Factors::BODY, Factors::F_BOOL, 91, Factors::V_NO, 'vn' => 21],

	/*
	 * Оба тайма > %pt
	 */
	1087 => [Factors::BODY, Factors::F_BOOL, 92, Factors::V_YES, 'vn' => 20],
	1088 => [Factors::BODY, Factors::F_BOOL, 92, Factors::V_NO, 'vn' => 21],

    5152 => [Factors::BODY, Factors::F_BOOL, 92, Factors::V_YES, 'vn' => 20],
    5153 => [Factors::BODY, Factors::F_BOOL, 92, Factors::V_NO, 'vn' => 21],

    5155 => [Factors::BODY, Factors::F_BOOL, 92, Factors::V_YES, 'vn' => 20],
    5156 => [Factors::BODY, Factors::F_BOOL, 92, Factors::V_NO, 'vn' => 21],

    5158 => [Factors::BODY, Factors::F_BOOL, 92, Factors::V_YES, 'vn' => 20],
    5159 => [Factors::BODY, Factors::F_BOOL, 92, Factors::V_NO, 'vn' => 21],

    /**
     * Все четверти < %pt
     */
    1089 => [Factors::BODY, Factors::F_BOOL, 380, Factors::V_YES, 'vn' => 20],
    1090 => [Factors::BODY, Factors::F_BOOL, 380, Factors::V_NO, 'vn' => 21],

    /**
     * Все четверти > %pt
     */
    1091 => [Factors::BODY, Factors::F_BOOL, 381, Factors::V_YES, 'vn' => 20],
    1092 => [Factors::BODY, Factors::F_BOOL, 381, Factors::V_NO, 'vn' => 21],

    /**
     * Все периоды < %pt
     */
    1093 => [Factors::BODY, Factors::F_BOOL, 382, Factors::V_YES, 'vn' => 20],
    1094 => [Factors::BODY, Factors::F_BOOL, 382, Factors::V_NO, 'vn' => 21],

    2534 => [Factors::BODY, Factors::F_BOOL, 382, Factors::V_YES, 'vn' => 20],
    2535 => [Factors::BODY, Factors::F_BOOL, 382, Factors::V_NO, 'vn' => 21],

    2537 => [Factors::BODY, Factors::F_BOOL, 382, Factors::V_YES, 'vn' => 20],
    2538 => [Factors::BODY, Factors::F_BOOL, 382, Factors::V_NO, 'vn' => 21],

    /**
     * Все периоды > %pt
     */
    1095 => [Factors::BODY, Factors::F_BOOL, 383, Factors::V_YES, 'vn' => 20],
    1096 => [Factors::BODY, Factors::F_BOOL, 383, Factors::V_NO, 'vn' => 21],

    2540 => [Factors::BODY, Factors::F_BOOL, 383, Factors::V_YES, 'vn' => 20],
    2541 => [Factors::BODY, Factors::F_BOOL, 383, Factors::V_NO, 'vn' => 21],

    2543 => [Factors::BODY, Factors::F_BOOL, 383, Factors::V_YES, 'vn' => 20],
    2544 => [Factors::BODY, Factors::F_BOOL, 383, Factors::V_NO, 'vn' => 21],

	/*
	 * Каждая команда забьет < %pt
	 */
    2324 => [Factors::BODY, Factors::F_BOOL, 125, Factors::V_YES, 'vn' => 20],
	2325 => [Factors::BODY, Factors::F_BOOL, 125, Factors::V_NO, 'vn' => 21],

    2546 => [Factors::BODY, Factors::F_BOOL, 125, Factors::V_YES, 'vn' => 20],
    2547 => [Factors::BODY, Factors::F_BOOL, 125, Factors::V_NO, 'vn' => 21],

    2549 => [Factors::BODY, Factors::F_BOOL, 125, Factors::V_YES, 'vn' => 20],
    2550 => [Factors::BODY, Factors::F_BOOL, 125, Factors::V_NO, 'vn' => 21],

	/*
	 * Каждая команда забьет > %pt
	 */
    2327 => [Factors::BODY, Factors::F_BOOL, 126, Factors::V_YES, 'vn' => 20],
    2328 => [Factors::BODY, Factors::F_BOOL, 126, Factors::V_NO, 'vn' => 21],

    2552 => [Factors::BODY, Factors::F_BOOL, 126, Factors::V_YES, 'vn' => 20],
    2553 => [Factors::BODY, Factors::F_BOOL, 126, Factors::V_NO, 'vn' => 21],

    2555 => [Factors::BODY, Factors::F_BOOL, 126, Factors::V_YES, 'vn' => 20],
    2556 => [Factors::BODY, Factors::F_BOOL, 126, Factors::V_NO, 'vn' => 21],

	/**
	 * Проход
	 */
	2820 => [Factors::BODY, Factors::F_TEAM, 5, Factors::V_FIRST, 'vn' => 17],
	2821 => [Factors::BODY, Factors::F_TEAM, 5, Factors::V_SECOND, 'vn' => 19],

    /**
     * Кто выиграет сет
     */
//    1606 => ['t' => Factor::F_TEAM, 'n' => 70, 'i' => Factor::V_FIRST],
//    1607 => ['t' => Factor::F_TEAM, 'n' => 70, 'i' => Factor::V_SECOND],

    /**
     * Итоговая победа
     */
    7035 => [Factors::BODY, Factors::F_TEAM, 351, Factors::V_FIRST, 'vn' => 17],
    7036 => [Factors::BODY, Factors::F_TEAM, 351, Factors::V_SECOND, 'vn' => 19],

    933 => [Factors::BODY, Factors::F_TEAM, 351, Factors::V_FIRST, 'vn' => 17],
    934 => [Factors::BODY, Factors::F_TEAM, 351, Factors::V_SECOND, 'vn' => 19],

    /**
     * Победитель в овертайме
     */
    2564 => [Factors::BODY, Factors::F_BOOL, 190, Factors::V_YES, 'vn' => 20],
    2565 => [Factors::BODY, Factors::F_BOOL, 190, Factors::V_NO, 'vn' => 21],

    /**
     * Победитель по буллитам
     */
    2567 => [Factors::BODY, Factors::F_BOOL, 189, Factors::V_YES, 'vn' => 20],
    2568 => [Factors::BODY, Factors::F_BOOL, 189, Factors::V_NO, 'vn' => 21],

    /**
     * Кто забьет первый гол
     */
//    709 => ['t' => Factor::F_ETEAM, 'n' => 115, 'i' => Factor::V_FIRST],
//    711 => ['t' => Factor::F_ETEAM, 'n' => 115, 'i' => Factor::V_EXCEPT],
//    710 => ['t' => Factor::F_ETEAM, 'n' => 115, 'i' => Factor::V_SECOND],
//
//    /**
//     * Кто забьет последний гол
//     */
//    721 => ['t' => Factor::F_ETEAM, 'n' => 116, 'i' => Factor::V_FIRST],
//    723 => ['t' => Factor::F_ETEAM, 'n' => 116, 'i' => Factor::V_EXCEPT],
//    722 => ['t' => Factor::F_ETEAM, 'n' => 116, 'i' => Factor::V_SECOND],

    /**
     * Следующий гол
     */
//    5390 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_FIRST, 'vn' => 17],
//    5392 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_EXCEPT, 'vn' => 18],
//    5391 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_SECOND, 'vn' => 19],

//    5394 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_FIRST, 'vn' => 17],
//    5396 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_EXCEPT, 'vn' => 18],
//    5395 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_SECOND, 'vn' => 19],

//    5398 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_FIRST, 'vn' => 17],
//    5400 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_EXCEPT, 'vn' => 18],
//    5399 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_SECOND, 'vn' => 19],

//    1598 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_FIRST, 'vn' => 17],
//    1600 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_EXCEPT, 'vn' => 18],
//    1599 => [Factors::BODY, Factors::F_ETEAM, 363, Factors::V_SECOND, 'vn' => 19],

    /**
     * Кто первым забьет свой гол N
     */
//    2331 => [Factors::BODY, Factors::F_ETEAM, 144, Factors::V_FIRST, 'vn' => 17],
//    2333 => [Factors::BODY, Factors::F_ETEAM, 144, Factors::V_EXCEPT, 'vn' => 18],
//    2332 => [Factors::BODY, Factors::F_ETEAM, 144, Factors::V_SECOND, 'vn' => 19],

//    2335 => [Factors::BODY, Factors::F_ETEAM, 144, Factors::V_FIRST, 'vn' => 17],
//    2337 => [Factors::BODY, Factors::F_ETEAM, 144, Factors::V_EXCEPT, 'vn' => 18],
//    2336 => [Factors::BODY, Factors::F_ETEAM, 144, Factors::V_SECOND, 'vn' => 19],

//    2339 => [Factors::BODY, Factors::F_ETEAM, 144, Factors::V_FIRST, 'vn' => 17],
//    2341 => [Factors::BODY, Factors::F_ETEAM, 144, Factors::V_EXCEPT, 'vn' => 18],
//    2340 => [Factors::BODY, Factors::F_ETEAM, 144, Factors::V_SECOND, 'vn' => 19],

    /**
     * Тотал чёт
     */
	698  => [Factors::BODY, Factors::F_BOOL, 6, Factors::V_YES, 'vn' => 20],
	699  => [Factors::BODY, Factors::F_BOOL, 6, Factors::V_NO, 'vn' => 21],

	/**
	 * Геймы в теннисе
	 */
	1609 => [Factors::BODY, Factors::F_GAMES, 10, Factors::V_FIRST, 'vn' => 17],
	1610 => [Factors::BODY, Factors::F_GAMES, 10, Factors::V_SECOND, 'vn' => 19],

	1747 => [Factors::BODY, Factors::F_GAMES, 10, Factors::V_FIRST, 'vn' => 17],
	1748 => [Factors::BODY, Factors::F_GAMES, 10, Factors::V_SECOND, 'vn' => 19],

	1750 => [Factors::BODY, Factors::F_GAMES, 10, Factors::V_FIRST, 'vn' => 17],
	1751 => [Factors::BODY, Factors::F_GAMES, 10, Factors::V_SECOND, 'vn' => 19],

	1753 => [Factors::BODY, Factors::F_GAMES, 10, Factors::V_FIRST, 'vn' => 17],
	1754 => [Factors::BODY, Factors::F_GAMES, 10, Factors::V_SECOND, 'vn' => 19],

    /**
     * Гейм %pt: будет счет 15:15
     */
    2893  => [Factors::BODY, Factors::F_BOOL, 384, Factors::V_YES, 'vn' => 20],
    2894  => [Factors::BODY, Factors::F_BOOL, 384, Factors::V_NO, 'vn' => 21],

    2909  => [Factors::BODY, Factors::F_BOOL, 384, Factors::V_YES, 'vn' => 20],
    2910  => [Factors::BODY, Factors::F_BOOL, 384, Factors::V_NO, 'vn' => 21],

    /**
     * Гейм %pt: будет счет 30:30
     */
    2899  => [Factors::BODY, Factors::F_BOOL, 385, Factors::V_YES, 'vn' => 20],
    2900  => [Factors::BODY, Factors::F_BOOL, 385, Factors::V_NO, 'vn' => 21],

    2915  => [Factors::BODY, Factors::F_BOOL, 385, Factors::V_YES, 'vn' => 20],
    2916  => [Factors::BODY, Factors::F_BOOL, 385, Factors::V_NO, 'vn' => 21],

    /**
     * Гейм %pt: будет счет 40:40
     */
    2902  => [Factors::BODY, Factors::F_BOOL, 386, Factors::V_YES, 'vn' => 20],
    2903  => [Factors::BODY, Factors::F_BOOL, 386, Factors::V_NO, 'vn' => 21],

    2918  => [Factors::BODY, Factors::F_BOOL, 386, Factors::V_YES, 'vn' => 20],
    2919  => [Factors::BODY, Factors::F_BOOL, 386, Factors::V_NO, 'vn' => 21],

    /**
     * Победа любой из команд с разницей в %pt
     */
//    1992  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_YES],
//    1993  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_NO],
//
//    1989  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_YES],
//    1990  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_NO],
//
//    1986  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_YES],
//    1987  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_NO],
//
//    1980  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_YES],
//    1981  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_NO],
//
//    1983  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_YES],
//    1984  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_NO],
//
//    1995  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_YES],
//    1996  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_NO],
//
//    1998  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_YES],
//    1999  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_NO],
//
//    2001  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_YES],
//    2002  => ['t' => Factor::F_BOOL, 'n' => 137, 'i' => Factor::V_NO],

	/**
	 * Счёт
	 */
	2079 => [Factors::BODY, Factors::F_SCORES, 38, 0, ['first' => 0, 'second' => 0], 'vn' => 26, 'symb' => '0:0'],
	2081 => [Factors::BODY, Factors::F_SCORES, 38, 1, ['first' => 0, 'second' => 1], 'vn' => 27, 'symb' => '0:1'],
	2082 => [Factors::BODY, Factors::F_SCORES, 38, 2, ['first' => 0, 'second' => 2], 'vn' => 28, 'symb' => '0:2'],
	2083 => [Factors::BODY, Factors::F_SCORES, 38, 3, ['first' => 0, 'second' => 3], 'vn' => 29, 'symb' => '0:3'],
	2084 => [Factors::BODY, Factors::F_SCORES, 38, 4, ['first' => 0, 'second' => 4], 'vn' => 30, 'symb' => '0:4'],
	2085 => [Factors::BODY, Factors::F_SCORES, 38, 5, ['first' => 0, 'second' => 5], 'vn' => 31, 'symb' => '0:5'],
	2086 => [Factors::BODY, Factors::F_SCORES, 38, 6, ['first' => 0, 'second' => 6], 'vn' => 32, 'symb' => '0:6'],
	2087 => [Factors::BODY, Factors::F_SCORES, 38, 7, ['first' => 0, 'second' => 7], 'vn' => 33, 'symb' => '0:7'],
	2088 => [Factors::BODY, Factors::F_SCORES, 38, 8, ['first' => 0, 'second' => 8], 'vn' => 34, 'symb' => '0:8'],
	2089 => [Factors::BODY, Factors::F_SCORES, 38, 9, ['first' => 0, 'second' => 9], 'vn' => 35, 'symb' => '0:9'],
	2090 => [Factors::BODY, Factors::F_SCORES, 38, 10, ['first' => 0, 'second' => 10], 'vn' => 36, 'symb' => '0:10'],

	2092 => [Factors::BODY, Factors::F_SCORES, 38, 11, ['first' => 1, 'second' => 0], 'vn' => 37, 'symb' => '1:0'],
	2093 => [Factors::BODY, Factors::F_SCORES, 38, 12, ['first' => 1, 'second' => 1], 'vn' => 38, 'symb' => '1:1'],
	2094 => [Factors::BODY, Factors::F_SCORES, 38, 13, ['first' => 1, 'second' => 2], 'vn' => 39, 'symb' => '1:2'],
	2095 => [Factors::BODY, Factors::F_SCORES, 38, 14, ['first' => 1, 'second' => 3], 'vn' => 40, 'symb' => '1:3'],
	2096 => [Factors::BODY, Factors::F_SCORES, 38, 15, ['first' => 1, 'second' => 4], 'vn' => 41, 'symb' => '1:4'],
	2097 => [Factors::BODY, Factors::F_SCORES, 38, 16, ['first' => 1, 'second' => 5], 'vn' => 42, 'symb' => '1:5'],
	2098 => [Factors::BODY, Factors::F_SCORES, 38, 17, ['first' => 1, 'second' => 6], 'vn' => 43, 'symb' => '1:6'],
	2099 => [Factors::BODY, Factors::F_SCORES, 38, 18, ['first' => 1, 'second' => 7], 'vn' => 44, 'symb' => '1:7'],
	2100 => [Factors::BODY, Factors::F_SCORES, 38, 19, ['first' => 1, 'second' => 8], 'vn' => 45, 'symb' => '1:8'],
	2101 => [Factors::BODY, Factors::F_SCORES, 38, 20, ['first' => 1, 'second' => 9], 'vn' => 46, 'symb' => '1:9'],
	2102 => [Factors::BODY, Factors::F_SCORES, 38, 21, ['first' => 1, 'second' => 10], 'vn' => 47, 'symb' => '1:10'],

	2103 => [Factors::BODY, Factors::F_SCORES, 38, 22, ['first' => 2, 'second' => 0], 'vn' => 48, 'symb' => '2:0'],
	2104 => [Factors::BODY, Factors::F_SCORES, 38, 23, ['first' => 2, 'second' => 1], 'vn' => 49, 'symb' => '2:1'],
	2105 => [Factors::BODY, Factors::F_SCORES, 38, 24, ['first' => 2, 'second' => 2], 'vn' => 50, 'symb' => '2:2'],
	2106 => [Factors::BODY, Factors::F_SCORES, 38, 25, ['first' => 2, 'second' => 3], 'vn' => 51, 'symb' => '2:3'],
	2107 => [Factors::BODY, Factors::F_SCORES, 38, 26, ['first' => 2, 'second' => 4], 'vn' => 52, 'symb' => '2:4'],
	2108 => [Factors::BODY, Factors::F_SCORES, 38, 27, ['first' => 2, 'second' => 5], 'vn' => 53, 'symb' => '2:5'],
	2109 => [Factors::BODY, Factors::F_SCORES, 38, 28, ['first' => 2, 'second' => 6], 'vn' => 54, 'symb' => '2:6'],
	2110 => [Factors::BODY, Factors::F_SCORES, 38, 29, ['first' => 2, 'second' => 7], 'vn' => 55, 'symb' => '2:7'],
	2111 => [Factors::BODY, Factors::F_SCORES, 38, 30, ['first' => 2, 'second' => 8], 'vn' => 56, 'symb' => '2:8'],
	2112 => [Factors::BODY, Factors::F_SCORES, 38, 31, ['first' => 2, 'second' => 9], 'vn' => 57, 'symb' => '2:9'],
	2113 => [Factors::BODY, Factors::F_SCORES, 38, 32, ['first' => 2, 'second' => 10], 'vn' => 58, 'symb' => '2:10'],

	2114 => [Factors::BODY, Factors::F_SCORES, 38, 33, ['first' => 3, 'second' => 0], 'vn' => 59, 'symb' => '3:0'],
	2115 => [Factors::BODY, Factors::F_SCORES, 38, 34, ['first' => 3, 'second' => 1], 'vn' => 60, 'symb' => '3:1'],
	2116 => [Factors::BODY, Factors::F_SCORES, 38, 35, ['first' => 3, 'second' => 2], 'vn' => 61, 'symb' => '3:2'],
	2117 => [Factors::BODY, Factors::F_SCORES, 38, 36, ['first' => 3, 'second' => 3], 'vn' => 62, 'symb' => '3:3'],
	2118 => [Factors::BODY, Factors::F_SCORES, 38, 37, ['first' => 3, 'second' => 4], 'vn' => 63, 'symb' => '3:4'],
	2119 => [Factors::BODY, Factors::F_SCORES, 38, 38, ['first' => 3, 'second' => 5], 'vn' => 64, 'symb' => '3:5'],
	2120 => [Factors::BODY, Factors::F_SCORES, 38, 39, ['first' => 3, 'second' => 6], 'vn' => 65, 'symb' => '3:6'],
	2121 => [Factors::BODY, Factors::F_SCORES, 38, 40, ['first' => 3, 'second' => 7], 'vn' => 66, 'symb' => '3:7'],
	2122 => [Factors::BODY, Factors::F_SCORES, 38, 41, ['first' => 3, 'second' => 8], 'vn' => 67, 'symb' => '3:8'],
	2123 => [Factors::BODY, Factors::F_SCORES, 38, 42, ['first' => 3, 'second' => 9], 'vn' => 68, 'symb' => '3:9'],
	2124 => [Factors::BODY, Factors::F_SCORES, 38, 43, ['first' => 3, 'second' => 10], 'vn' => 69, 'symb' => '3:10'],

	2125 => [Factors::BODY, Factors::F_SCORES, 38, 44, ['first' => 4, 'second' => 0], 'vn' => 70, 'symb' => '4:0'],
	2126 => [Factors::BODY, Factors::F_SCORES, 38, 45, ['first' => 4, 'second' => 1], 'vn' => 71, 'symb' => '4:1'],
	2127 => [Factors::BODY, Factors::F_SCORES, 38, 46, ['first' => 4, 'second' => 2], 'vn' => 72, 'symb' => '4:2'],
	2128 => [Factors::BODY, Factors::F_SCORES, 38, 47, ['first' => 4, 'second' => 3], 'vn' => 73, 'symb' => '4:3'],
	2129 => [Factors::BODY, Factors::F_SCORES, 38, 48, ['first' => 4, 'second' => 4], 'vn' => 74, 'symb' => '4:4'],
	2130 => [Factors::BODY, Factors::F_SCORES, 38, 49, ['first' => 4, 'second' => 5], 'vn' => 75, 'symb' => '4:5'],
	2131 => [Factors::BODY, Factors::F_SCORES, 38, 50, ['first' => 4, 'second' => 6], 'vn' => 76, 'symb' => '4:6'],
	2132 => [Factors::BODY, Factors::F_SCORES, 38, 51, ['first' => 4, 'second' => 7], 'vn' => 77, 'symb' => '4:7'],
	2133 => [Factors::BODY, Factors::F_SCORES, 38, 52, ['first' => 4, 'second' => 8], 'vn' => 78, 'symb' => '4:8'],
	2134 => [Factors::BODY, Factors::F_SCORES, 38, 53, ['first' => 4, 'second' => 9], 'vn' => 79, 'symb' => '4:9'],
	2135 => [Factors::BODY, Factors::F_SCORES, 38, 54, ['first' => 4, 'second' => 10], 'vn' => 80, 'symb' => '4:10'],

	2136 => [Factors::BODY, Factors::F_SCORES, 38, 55, ['first' => 5, 'second' => 0], 'vn' => 81, 'symb' => '5:0'],
	2137 => [Factors::BODY, Factors::F_SCORES, 38, 56, ['first' => 5, 'second' => 1], 'vn' => 82, 'symb' => '5:1'],
	2138 => [Factors::BODY, Factors::F_SCORES, 38, 57, ['first' => 5, 'second' => 2], 'vn' => 83, 'symb' => '5:2'],
	2139 => [Factors::BODY, Factors::F_SCORES, 38, 58, ['first' => 5, 'second' => 3], 'vn' => 84, 'symb' => '5:3'],
	2140 => [Factors::BODY, Factors::F_SCORES, 38, 59, ['first' => 5, 'second' => 4], 'vn' => 85, 'symb' => '5:4'],
	2141 => [Factors::BODY, Factors::F_SCORES, 38, 60, ['first' => 5, 'second' => 5], 'vn' => 86, 'symb' => '5:5'],
	2142 => [Factors::BODY, Factors::F_SCORES, 38, 61, ['first' => 5, 'second' => 6], 'vn' => 87, 'symb' => '5:6'],
	2143 => [Factors::BODY, Factors::F_SCORES, 38, 62, ['first' => 5, 'second' => 7], 'vn' => 88, 'symb' => '5:7'],
	2144 => [Factors::BODY, Factors::F_SCORES, 38, 63, ['first' => 5, 'second' => 8], 'vn' => 89, 'symb' => '5:8'],
	2145 => [Factors::BODY, Factors::F_SCORES, 38, 64, ['first' => 5, 'second' => 9], 'vn' => 90, 'symb' => '5:9'],
	2146 => [Factors::BODY, Factors::F_SCORES, 38, 65, ['first' => 5, 'second' => 10], 'vn' => 91, 'symb' => '5:10'],

	2147 => [Factors::BODY, Factors::F_SCORES, 38, 66, ['first' => 6, 'second' => 0], 'vn' => 92, 'symb' => '6:0'],
	2148 => [Factors::BODY, Factors::F_SCORES, 38, 67, ['first' => 6, 'second' => 1], 'vn' => 93, 'symb' => '6:1'],
	2149 => [Factors::BODY, Factors::F_SCORES, 38, 68, ['first' => 6, 'second' => 2], 'vn' => 94, 'symb' => '6:2'],
	2150 => [Factors::BODY, Factors::F_SCORES, 38, 69, ['first' => 6, 'second' => 3], 'vn' => 95, 'symb' => '6:3'],
	2151 => [Factors::BODY, Factors::F_SCORES, 38, 70, ['first' => 6, 'second' => 4], 'vn' => 96, 'symb' => '6:4'],
	2152 => [Factors::BODY, Factors::F_SCORES, 38, 71, ['first' => 6, 'second' => 5], 'vn' => 97, 'symb' => '6:5'],
	2153 => [Factors::BODY, Factors::F_SCORES, 38, 72, ['first' => 6, 'second' => 6], 'vn' => 98, 'symb' => '6:6'],
	2154 => [Factors::BODY, Factors::F_SCORES, 38, 73, ['first' => 6, 'second' => 7], 'vn' => 99, 'symb' => '6:7'],
	2155 => [Factors::BODY, Factors::F_SCORES, 38, 74, ['first' => 6, 'second' => 8], 'vn' => 100, 'symb' => '6:8'],
	2156 => [Factors::BODY, Factors::F_SCORES, 38, 75, ['first' => 6, 'second' => 9], 'vn' => 101, 'symb' => '6:9'],
	2157 => [Factors::BODY, Factors::F_SCORES, 38, 76, ['first' => 6, 'second' => 10], 'vn' => 102, 'symb' => '6:10'],

	2158 => [Factors::BODY, Factors::F_SCORES, 38, 77, ['first' => 7, 'second' => 0], 'vn' => 103, 'symb' => '7:0'],
	2159 => [Factors::BODY, Factors::F_SCORES, 38, 78, ['first' => 7, 'second' => 1], 'vn' => 104, 'symb' => '7:1'],
	2160 => [Factors::BODY, Factors::F_SCORES, 38, 79, ['first' => 7, 'second' => 2], 'vn' => 105, 'symb' => '7:2'],
	2161 => [Factors::BODY, Factors::F_SCORES, 38, 80, ['first' => 7, 'second' => 3], 'vn' => 106, 'symb' => '7:3'],
	2162 => [Factors::BODY, Factors::F_SCORES, 38, 81, ['first' => 7, 'second' => 4], 'vn' => 107, 'symb' => '7:4'],
	2163 => [Factors::BODY, Factors::F_SCORES, 38, 82, ['first' => 7, 'second' => 5], 'vn' => 108, 'symb' => '7:5'],
	2164 => [Factors::BODY, Factors::F_SCORES, 38, 83, ['first' => 7, 'second' => 6], 'vn' => 109, 'symb' => '7:6'],
	2165 => [Factors::BODY, Factors::F_SCORES, 38, 84, ['first' => 7, 'second' => 7], 'vn' => 110, 'symb' => '7:7'],
	2166 => [Factors::BODY, Factors::F_SCORES, 38, 85, ['first' => 7, 'second' => 8], 'vn' => 111, 'symb' => '7:8'],
	2167 => [Factors::BODY, Factors::F_SCORES, 38, 86, ['first' => 7, 'second' => 9], 'vn' => 112, 'symb' => '7:9'],
	2168 => [Factors::BODY, Factors::F_SCORES, 38, 87, ['first' => 7, 'second' => 10], 'vn' => 113, 'symb' => '7:10'],

	2169 => [Factors::BODY, Factors::F_SCORES, 38, 88, ['first' => 8, 'second' => 0], 'vn' => 114, 'symb' => '8:0'],
	2170 => [Factors::BODY, Factors::F_SCORES, 38, 89, ['first' => 8, 'second' => 1], 'vn' => 115, 'symb' => '8:1'],
	2171 => [Factors::BODY, Factors::F_SCORES, 38, 90, ['first' => 8, 'second' => 2], 'vn' => 116, 'symb' => '8:2'],
	2172 => [Factors::BODY, Factors::F_SCORES, 38, 91, ['first' => 8, 'second' => 3], 'vn' => 117, 'symb' => '8:3'],
	2173 => [Factors::BODY, Factors::F_SCORES, 38, 92, ['first' => 8, 'second' => 4], 'vn' => 118, 'symb' => '8:4'],
	2174 => [Factors::BODY, Factors::F_SCORES, 38, 93, ['first' => 8, 'second' => 5], 'vn' => 119, 'symb' => '8:5'],
	2175 => [Factors::BODY, Factors::F_SCORES, 38, 94, ['first' => 8, 'second' => 6], 'vn' => 120, 'symb' => '8:6'],
	2176 => [Factors::BODY, Factors::F_SCORES, 38, 95, ['first' => 8, 'second' => 7], 'vn' => 121, 'symb' => '8:7'],
	2177 => [Factors::BODY, Factors::F_SCORES, 38, 96, ['first' => 8, 'second' => 8], 'vn' => 122, 'symb' => '8:8'],
	2178 => [Factors::BODY, Factors::F_SCORES, 38, 97, ['first' => 8, 'second' => 9], 'vn' => 123, 'symb' => '8:9'],
	2179 => [Factors::BODY, Factors::F_SCORES, 38, 98, ['first' => 8, 'second' => 10], 'vn' => 124, 'symb' => '8:10'],

	2180 => [Factors::BODY, Factors::F_SCORES, 38, 99, ['first' => 9, 'second' => 0], 'vn' => 125, 'symb' => '9:0'],
	2181 => [Factors::BODY, Factors::F_SCORES, 38, 100, ['first' => 9, 'second' => 1], 'vn' => 126, 'symb' => '9:1'],
	2182 => [Factors::BODY, Factors::F_SCORES, 38, 101, ['first' => 9, 'second' => 2], 'vn' => 127, 'symb' => '9:2'],
	2183 => [Factors::BODY, Factors::F_SCORES, 38, 102, ['first' => 9, 'second' => 3], 'vn' => 128, 'symb' => '9:3'],
	2184 => [Factors::BODY, Factors::F_SCORES, 38, 103, ['first' => 9, 'second' => 4], 'vn' => 129, 'symb' => '9:4'],
	2185 => [Factors::BODY, Factors::F_SCORES, 38, 104, ['first' => 9, 'second' => 5], 'vn' => 130, 'symb' => '9:5'],
	2186 => [Factors::BODY, Factors::F_SCORES, 38, 105, ['first' => 9, 'second' => 6], 'vn' => 131, 'symb' => '9:6'],
	2187 => [Factors::BODY, Factors::F_SCORES, 38, 106, ['first' => 9, 'second' => 7], 'vn' => 132, 'symb' => '9:7'],
	2188 => [Factors::BODY, Factors::F_SCORES, 38, 107, ['first' => 9, 'second' => 8], 'vn' => 133, 'symb' => '9:8'],
	2189 => [Factors::BODY, Factors::F_SCORES, 38, 108, ['first' => 9, 'second' => 9], 'vn' => 134, 'symb' => '9:9'],
	2190 => [Factors::BODY, Factors::F_SCORES, 38, 109, ['first' => 9, 'second' => 10], 'vn' => 135, 'symb' => '9:10'],

	2191 => [Factors::BODY, Factors::F_SCORES, 38, 110, ['first' => 10, 'second' => 0], 'vn' => 136, 'symb' => '10:0'],
	2192 => [Factors::BODY, Factors::F_SCORES, 38, 111, ['first' => 10, 'second' => 1], 'vn' => 137, 'symb' => '10:1'],
	2193 => [Factors::BODY, Factors::F_SCORES, 38, 112, ['first' => 10, 'second' => 2], 'vn' => 138, 'symb' => '10:2'],
	2194 => [Factors::BODY, Factors::F_SCORES, 38, 113, ['first' => 10, 'second' => 3], 'vn' => 139, 'symb' => '10:3'],
	2195 => [Factors::BODY, Factors::F_SCORES, 38, 114, ['first' => 10, 'second' => 4], 'vn' => 140, 'symb' => '10:4'],
	2196 => [Factors::BODY, Factors::F_SCORES, 38, 115, ['first' => 10, 'second' => 5], 'vn' => 141, 'symb' => '10:5'],
	2197 => [Factors::BODY, Factors::F_SCORES, 38, 116, ['first' => 10, 'second' => 6], 'vn' => 142, 'symb' => '10:6'],
	2198 => [Factors::BODY, Factors::F_SCORES, 38, 117, ['first' => 10, 'second' => 7], 'vn' => 143, 'symb' => '10:7'],
	2199 => [Factors::BODY, Factors::F_SCORES, 38, 118, ['first' => 10, 'second' => 8], 'vn' => 144, 'symb' => '10:8'],
	2200 => [Factors::BODY, Factors::F_SCORES, 38, 119, ['first' => 10, 'second' => 9], 'vn' => 145, 'symb' => '10:9'],
	2201 => [Factors::BODY, Factors::F_SCORES, 38, 120, ['first' => 10, 'second' => 10], 'vn' => 146, 'symb' => '10:10'],

	943 => [Factors::BODY, Factors::F_SCORES, 38, 22, ['first' => 2, 'second' => 0], 'vn' => 48, 'symb' => '2:0'], //2 0
	944 => [Factors::BODY, Factors::F_SCORES, 38, 23, ['first' => 2, 'second' => 1], 'vn' => 49, 'symb' => '2:1'], //2 1
	953 => [Factors::BODY, Factors::F_SCORES, 38, 25, ['first' => 2, 'second' => 3], 'vn' => 51, 'symb' => '2:3'], //2 3

	945 => [Factors::BODY, Factors::F_SCORES, 38, 2, ['first' => 0, 'second' => 2], 'vn' => 28, 'symb' => '0:2'],
	951 => [Factors::BODY, Factors::F_SCORES, 38, 3, ['first' => 0, 'second' => 3], 'vn' => 29, 'symb' => '0:3'],
	946 => [Factors::BODY, Factors::F_SCORES, 38, 13, ['first' => 1, 'second' => 2], 'vn' => 39, 'symb' => '1:2'],
	952 => [Factors::BODY, Factors::F_SCORES, 38, 14, ['first' => 1, 'second' => 3], 'vn' => 40, 'symb' => '1:3'],

	948  => [Factors::BODY, Factors::F_SCORES, 38, 33, ['first' => 3, 'second' => 0], 'vn' => 59, 'symb' => '3:0'],
	949  => [Factors::BODY, Factors::F_SCORES, 38, 34, ['first' => 3, 'second' => 1], 'vn' => 60, 'symb' => '3:1'],
	950  => [Factors::BODY, Factors::F_SCORES, 38, 35, ['first' => 3, 'second' => 2], 'vn' => 61, 'symb' => '3:3'],

    /**
     * Счет гейма
     */
    2922  => [Factors::BODY, Factors::F_SCORES, 387, 10, ['first' => 40, 'second' => 0], 'vn' => 159, 'symb' => '40:00'],
    2931  => [Factors::BODY, Factors::F_SCORES, 387, 10, ['first' => 40, 'second' => 0], 'vn' => 159, 'symb' => '40:00'],

    2923  => [Factors::BODY, Factors::F_SCORES, 387, 11, ['first' => 40, 'second' => 15], 'vn' => 160, 'symb' => '40:15'],
    2932  => [Factors::BODY, Factors::F_SCORES, 387, 11, ['first' => 40, 'second' => 15], 'vn' => 160, 'symb' => '40:15'],

    2924  => [Factors::BODY, Factors::F_SCORES, 387, 12, ['first' => 40, 'second' => 30], 'vn' => 161, 'symb' => '40:30'],
    2933  => [Factors::BODY, Factors::F_SCORES, 387, 12, ['first' => 40, 'second' => 30], 'vn' => 161, 'symb' => '40:30'],

    2925  => [Factors::BODY, Factors::F_SCORES, 387, 13, ['first' => 'AVG', 'second' => 40], 'vn' => 162, 'symb' => 'AVG:40'],
    2934  => [Factors::BODY, Factors::F_SCORES, 387, 13, ['first' => 'AVG', 'second' => 40], 'vn' => 162, 'symb' => 'AVG:40'],

    2926  => [Factors::BODY, Factors::F_SCORES, 387, 20, ['first' => 0, 'second' => 40], 'vn' => 163, 'symb' => '00:40'],
    2935  => [Factors::BODY, Factors::F_SCORES, 387, 20, ['first' => 0, 'second' => 40], 'vn' => 163, 'symb' => '00:40'],

    2927  => [Factors::BODY, Factors::F_SCORES, 387, 21, ['first' => 15, 'second' => 40], 'vn' => 164, 'symb' => '15:40'],
    2936  => [Factors::BODY, Factors::F_SCORES, 387, 21, ['first' => 15, 'second' => 40], 'vn' => 164, 'symb' => '15:40'],

    2928  => [Factors::BODY, Factors::F_SCORES, 387, 22, ['first' => 30, 'second' => 40], 'vn' => 165, 'symb' => '30:40'],
    2937  => [Factors::BODY, Factors::F_SCORES, 387, 22, ['first' => 30, 'second' => 40], 'vn' => 165, 'symb' => '30:40'],

    2929  => [Factors::BODY, Factors::F_SCORES, 387, 23, ['first' => 40, 'second' => 'AVG'], 'vn' => 166, 'symb' => '40:AVG'],
    2938  => [Factors::BODY, Factors::F_SCORES, 387, 23, ['first' => 40, 'second' => 'AVG'], 'vn' => 166, 'symb' => '40:AVG'],

    /**
     * Счёт сета
     */
    955  => [Factors::BODY, Factors::F_SCORES, 38, 66, ['first' => 6, 'second' => 0], 'vn' => 92, 'symb' => '6:0'],
    956  => [Factors::BODY, Factors::F_SCORES, 38, 67, ['first' => 6, 'second' => 1], 'vn' => 93, 'symb' => '6:1'],
    957  => [Factors::BODY, Factors::F_SCORES, 38, 68, ['first' => 6, 'second' => 2], 'vn' => 94, 'symb' => '6:2'],
    958  => [Factors::BODY, Factors::F_SCORES, 38, 69, ['first' => 6, 'second' => 3], 'vn' => 95, 'symb' => '6:3'],
    959  => [Factors::BODY, Factors::F_SCORES, 38, 70, ['first' => 6, 'second' => 4], 'vn' => 96, 'symb' => '6:4'],
    968  => [Factors::BODY, Factors::F_SCORES, 38, 73, ['first' => 6, 'second' => 7], 'vn' => 97, 'symb' => '6:7'],
    960  => [Factors::BODY, Factors::F_SCORES, 38, 82, ['first' => 7, 'second' => 5], 'vn' => 108, 'symb' => '7:5'],
    961  => [Factors::BODY, Factors::F_SCORES, 38, 83, ['first' => 7, 'second' => 6], 'vn' => 109, 'symb' => '7:6'],
    962  => [Factors::BODY, Factors::F_SCORES, 38, 6,  ['first' => 0, 'second' => 6], 'vn' => 32, 'symb' => '0:6'],
    963  => [Factors::BODY, Factors::F_SCORES, 38, 17, ['first' => 1, 'second' => 6], 'vn' => 43, 'symb' => '1:6'],
    964  => [Factors::BODY, Factors::F_SCORES, 38, 28, ['first' => 2, 'second' => 6], 'vn' => 54, 'symb' => '2:6'],
    965  => [Factors::BODY, Factors::F_SCORES, 38, 39, ['first' => 3, 'second' => 6], 'vn' => 65, 'symb' => '3:6'],
    966  => [Factors::BODY, Factors::F_SCORES, 38, 50, ['first' => 4, 'second' => 6], 'vn' => 76, 'symb' => '4:6'],
    967  => [Factors::BODY, Factors::F_SCORES, 38, 62, ['first' => 5, 'second' => 7], 'vn' => 88, 'symb' => '5:6'],

    /**
     * Результативность таймов
     */
    831 => [Factors::BODY, Factors::F_EFFECTIVE, 40, Factors::V_FIRST, 'vn' => 147],
    832 => [Factors::BODY, Factors::F_EFFECTIVE, 40, Factors::V_DRAW, 'vn' => 148],
    833 => [Factors::BODY, Factors::F_EFFECTIVE, 40, Factors::V_SECOND, 'vn' => 149],

    /**
     * Тайм/Матч
     */
	1011 => [Factors::BODY, Factors::F_PERIOD_M, 39, 0, ['first' => 0, 'second' => 0], 'vn' => 150, 'symb' => 'НН'],
	1010 => [Factors::BODY, Factors::F_PERIOD_M, 39, 1, ['first' => 0, 'second' => 1], 'vn' => 151, 'symb' => 'НП1'],
	1012 => [Factors::BODY, Factors::F_PERIOD_M, 39, 2, ['first' => 0, 'second' => 2], 'vn' => 152, 'symb' => 'НП2'],
	1008 => [Factors::BODY, Factors::F_PERIOD_M, 39, 10, ['first' => 1, 'second' => 0], 'vn' => 153, 'symb' => 'П1Н'],
	1007 => [Factors::BODY, Factors::F_PERIOD_M, 39, 11, ['first' => 1, 'second' => 1], 'vn' => 154, 'symb' => 'П1П1'],
	1009 => [Factors::BODY, Factors::F_PERIOD_M, 39, 12, ['first' => 1, 'second' => 2], 'vn' => 155, 'symb' => 'П1П2'],
	1014 => [Factors::BODY, Factors::F_PERIOD_M, 39, 20, ['first' => 2, 'second' => 0], 'vn' => 156, 'symb' => 'П2Н'],
	1013 => [Factors::BODY, Factors::F_PERIOD_M, 39, 21, ['first' => 2, 'second' => 1], 'vn' => 157, 'symb' => 'П2П1'],
	1015 => [Factors::BODY, Factors::F_PERIOD_M, 39, 22, ['first' => 2, 'second' => 2], 'vn' => 158, 'symb' => 'П2П2'],

    /**
     * Период/Матч
     */
//    1001 => ['t' => Factor::F_PERIOD_M, 'n' => 72, 'i' => 00, 'symbol' => 'НН'],
//    1000 => ['t' => Factor::F_PERIOD_M, 'n' => 72, 'i' => 01, 'symbol' => 'НП1'],
//    1002 => ['t' => Factor::F_PERIOD_M, 'n' => 72, 'i' => 02, 'symbol' => 'НП2'],
//    998  => ['t' => Factor::F_PERIOD_M, 'n' => 72, 'i' => 10, 'symbol' => 'П1Н'],
//    997  => ['t' => Factor::F_PERIOD_M, 'n' => 72, 'i' => 11, 'symbol' => 'П1П1'],
//    999  => ['t' => Factor::F_PERIOD_M, 'n' => 72, 'i' => 12, 'symbol' => 'П1П2'],
//    1004 => ['t' => Factor::F_PERIOD_M, 'n' => 72, 'i' => 20, 'symbol' => 'П2Н'],
//    1003 => ['t' => Factor::F_PERIOD_M, 'n' => 72, 'i' => 21, 'symbol' => 'П2П1'],
//    1005 => ['t' => Factor::F_PERIOD_M, 'n' => 72, 'i' => 22, 'symbol' => 'П2П2'],

    /**
     * Сет/Матч
     */
	1017 => [Factors::BODY, Factors::F_PERIOD_M, 64, 11, ['first' => 1, 'second' => 1], 'vn' => 154, 'symb' => 'П1П1'],
	1018 => [Factors::BODY, Factors::F_PERIOD_M, 64, 12, ['first' => 1, 'second' => 2], 'vn' => 155, 'symb' => 'П1П2'],
	1019 => [Factors::BODY, Factors::F_PERIOD_M, 64, 21, ['first' => 2, 'second' => 1], 'vn' => 157, 'symb' => 'П2П1'],
	1020 => [Factors::BODY, Factors::F_PERIOD_M, 64, 22, ['first' => 2, 'second' => 2], 'vn' => 158, 'symb' => 'П2П2'],

    /**
     * Иннинг/Матч
     */
//    1293 => ['t' => Factor::F_PERIOD_M, 'n' => 74, 'i' => 01, 'symbol' => 'НП1'],
//    1294 => ['t' => Factor::F_PERIOD_M, 'n' => 74, 'i' => 02, 'symbol' => 'НП2'],
//    1291 => ['t' => Factor::F_PERIOD_M, 'n' => 74, 'i' => 11, 'symbol' => 'П1П1'],
//    1292 => ['t' => Factor::F_PERIOD_M, 'n' => 74, 'i' => 12, 'symbol' => 'П1П2'],
//    1295 => ['t' => Factor::F_PERIOD_M, 'n' => 74, 'i' => 21, 'symbol' => 'П2П1'],
//    1296 => ['t' => Factor::F_PERIOD_M, 'n' => 74, 'i' => 22, 'symbol' => 'П2П2'],


	/**
	 * I BOOL
	 */

    /**
     * Победа с разницей в %pt
     */
//    1041 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_YES],
//    1914 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_NO],
//
//    1042 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_YES],
//    1918 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_NO],
//
//    1043 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_YES],
//    1922 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_NO],
//
//    1045 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_YES],
//    1933 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_NO],
//
//    1046 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_YES],
//    1937 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_NO],
//
//    1047 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_YES],
//    1941 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_NO],
//
//    1048 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_YES],
//    1945 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_FIRST_NO],
//
//    879  => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//    1959 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//
//    880  => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//    1961 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//
//    881  => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//    1963 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//
//    882  => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//    1965 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//
//    883  => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//    1967 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//
//    884  => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//    1969 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//
//    885  => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//    1971 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//
//    886  => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],
//    1973 => ['t' => Factor::F_I_BOOL, 'n' => 138, 'i' => Factor::V_SECOND_YES],

    /**
     * Сухая победа
     */
//    1055 => ['t' => Factor::F_I_BOOL, 'n' => 34, 'i' => Factor::V_FIRST_YES],
//    1902 => ['t' => Factor::F_I_BOOL, 'n' => 34, 'i' => Factor::V_FIRST_NO],
//
//    893  => ['t' => Factor::F_I_BOOL, 'n' => 34, 'i' => Factor::V_SECOND_YES],
//    1947 => ['t' => Factor::F_I_BOOL, 'n' => 34, 'i' => Factor::V_SECOND_NO],

    /**
     * Выиграет оба тайма
     */
//    1058 => ['t' => Factor::F_I_BOOL, 'n' => 114, 'i' => Factor::V_FIRST_YES],
//    1904 => ['t' => Factor::F_I_BOOL, 'n' => 114, 'i' => Factor::V_FIRST_NO],
//
//    896  => ['t' => Factor::F_I_BOOL, 'n' => 114, 'i' => Factor::V_SECOND_YES],
//    1949 => ['t' => Factor::F_I_BOOL, 'n' => 114, 'i' => Factor::V_SECOND_NO],

    /**
     * Будет доп. время и победит
     */
//    2570 => ['t' => Factor::F_I_BOOL, 'n' => 195, 'i' => Factor::V_FIRST_YES],
//    2571 => ['t' => Factor::F_I_BOOL, 'n' => 195, 'i' => Factor::V_FIRST_NO],
//
//    2582 => ['t' => Factor::F_I_BOOL, 'n' => 195, 'i' => Factor::V_SECOND_YES],
//    2583 => ['t' => Factor::F_I_BOOL, 'n' => 195, 'i' => Factor::V_SECOND_NO],

    /**
     * Будет серия пенальти и победит
     */
//    2573 => ['t' => Factor::F_I_BOOL, 'n' => 196, 'i' => Factor::V_FIRST_YES],
//    2574 => ['t' => Factor::F_I_BOOL, 'n' => 196, 'i' => Factor::V_FIRST_NO],
//
//    2585 => ['t' => Factor::F_I_BOOL, 'n' => 196, 'i' => Factor::V_SECOND_YES],
//    2586 => ['t' => Factor::F_I_BOOL, 'n' => 196, 'i' => Factor::V_SECOND_NO],

    /**
     * Победа по буллитам
     */
    2579 => [Factors::BODY, Factors::F_I_BOOL, 192, Factors::V_FIRST_YES, 'vn' => 22],
    2580 => [Factors::BODY, Factors::F_I_BOOL, 192, Factors::V_FIRST_NO, 'vn' => 23],

    2591 => [Factors::BODY, Factors::F_I_BOOL, 192, Factors::V_SECOND_YES, 'vn' => 24],
    2592 => [Factors::BODY, Factors::F_I_BOOL, 192, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Победа в овертайме
     */
    2576 => [Factors::BODY, Factors::F_I_BOOL, 193, Factors::V_FIRST_YES, 'vn' => 22],
    2577 => [Factors::BODY, Factors::F_I_BOOL, 193, Factors::V_FIRST_NO, 'vn' => 23],

    3284 => [Factors::BODY, Factors::F_I_BOOL, 193, Factors::V_FIRST_YES, 'vn' => 22],
    3285 => [Factors::BODY, Factors::F_I_BOOL, 193, Factors::V_FIRST_NO, 'vn' => 23],

    2588 => [Factors::BODY, Factors::F_I_BOOL, 193, Factors::V_SECOND_YES, 'vn' => 24],
    2589 => [Factors::BODY, Factors::F_I_BOOL, 193, Factors::V_SECOND_NO, 'vn' => 25],

    3287 => [Factors::BODY, Factors::F_I_BOOL, 193, Factors::V_SECOND_YES, 'vn' => 24],
    3288 => [Factors::BODY, Factors::F_I_BOOL, 193, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Проход в доп. время
     */
//    2839 => ['t' => Factor::F_I_BOOL, 'n' => 182, 'i' => Factor::V_FIRST_YES],
//    2840 => ['t' => Factor::F_I_BOOL, 'n' => 182, 'i' => Factor::V_FIRST_NO],
//
//    2851 => ['t' => Factor::F_I_BOOL, 'n' => 182, 'i' => Factor::V_SECOND_YES],
//    2852 => ['t' => Factor::F_I_BOOL, 'n' => 182, 'i' => Factor::V_SECOND_NO],

    /**
     * Проход по серии пинальти
     */
//    2842 => ['t' => Factor::F_I_BOOL, 'n' => 183, 'i' => Factor::V_FIRST_YES],
//    2843 => ['t' => Factor::F_I_BOOL, 'n' => 183, 'i' => Factor::V_FIRST_NO],
//
//    2854 => ['t' => Factor::F_I_BOOL, 'n' => 183, 'i' => Factor::V_SECOND_YES],
//    2855 => ['t' => Factor::F_I_BOOL, 'n' => 183, 'i' => Factor::V_SECOND_NO],

    /**
     * Проход в овертайме
     */
//    2845 => ['t' => Factor::F_I_BOOL, 'n' => 184, 'i' => Factor::V_FIRST_YES],
//    2846 => ['t' => Factor::F_I_BOOL, 'n' => 184, 'i' => Factor::V_FIRST_NO],
//
//    2857 => ['t' => Factor::F_I_BOOL, 'n' => 184, 'i' => Factor::V_SECOND_YES],
//    2858 => ['t' => Factor::F_I_BOOL, 'n' => 184, 'i' => Factor::V_SECOND_NO],

    /**
     * Проход по буллитам
     */
//    2848 => ['t' => Factor::F_I_BOOL, 'n' => 185, 'i' => Factor::V_FIRST_YES],
//    2849 => ['t' => Factor::F_I_BOOL, 'n' => 185, 'i' => Factor::V_FIRST_NO],
//
//    2860 => ['t' => Factor::F_I_BOOL, 'n' => 185, 'i' => Factor::V_SECOND_YES],
//    2861 => ['t' => Factor::F_I_BOOL, 'n' => 185, 'i' => Factor::V_SECOND_NO],


    /**
     * Выиграет хотя бы один тайм
     */
	1741 => [Factors::BODY, Factors::F_I_BOOL, 59, Factors::V_FIRST_YES, 'vn' => 22],
	1742 => [Factors::BODY, Factors::F_I_BOOL, 59, Factors::V_FIRST_NO, 'vn' => 23],

	1744 => [Factors::BODY, Factors::F_I_BOOL, 59, Factors::V_SECOND_YES, 'vn' => 24],
	1745 => [Factors::BODY, Factors::F_I_BOOL, 59, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьет в обоих таймах
     */
	1069 => [Factors::BODY, Factors::F_I_BOOL, 32, Factors::V_FIRST_YES, 'vn' => 22],
	1070 => [Factors::BODY, Factors::F_I_BOOL, 32, Factors::V_FIRST_NO, 'vn' => 23],

	907  => [Factors::BODY, Factors::F_I_BOOL, 32, Factors::V_SECOND_YES, 'vn' => 24],
	908  => [Factors::BODY, Factors::F_I_BOOL, 32, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Чётный инд. тотал
     */
    4881 => [Factors::BODY, Factors::F_I_BOOL, 355, Factors::V_FIRST_YES, 'vn' => 22],
    4882 => [Factors::BODY, Factors::F_I_BOOL, 355, Factors::V_FIRST_NO, 'vn' => 23],

    4884 => [Factors::BODY, Factors::F_I_BOOL, 355, Factors::V_SECOND_YES, 'vn' => 24],
    4885 => [Factors::BODY, Factors::F_I_BOOL, 355, Factors::V_SECOND_NO, 'vn' => 25],

	/**
	 * Обе забьют и победит
	 */
    4832 => [Factors::BODY, Factors::F_I_BOOL, 354, Factors::V_FIRST_YES, 'vn' => 22],
    4833 => [Factors::BODY, Factors::F_I_BOOL, 354, Factors::V_FIRST_NO, 'vn' => 23],

    4838 => [Factors::BODY, Factors::F_I_BOOL, 354, Factors::V_SECOND_YES, 'vn' => 24],
    4839 => [Factors::BODY, Factors::F_I_BOOL, 354, Factors::V_SECOND_NO, 'vn' => 25],

	3379 => [Factors::BODY, Factors::F_I_BOOL, 354, Factors::V_FIRST_YES, 'vn' => 22],
	3380 => [Factors::BODY, Factors::F_I_BOOL, 354, Factors::V_FIRST_NO, 'vn' => 23],

	3382 => [Factors::BODY, Factors::F_I_BOOL, 354, Factors::V_SECOND_YES, 'vn' => 24],
	3383 => [Factors::BODY, Factors::F_I_BOOL, 354, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Выиграет все периоды
     */
//    2628 => ['t' => Factor::F_I_BOOL, 'n' => 63, 'i' => Factor::V_FIRST_YES],
//    2629 => ['t' => Factor::F_I_BOOL, 'n' => 63, 'i' => Factor::V_FIRST_NO],
//
//    2632 => ['t' => Factor::F_I_BOOL, 'n' => 63, 'i' => Factor::V_SECOND_YES],
//    2633 => ['t' => Factor::F_I_BOOL, 'n' => 63, 'i' => Factor::V_SECOND_NO],

    /**
     * Все периоды < %pt
     */
//    7148 => ['t' => Factor::F_I_BOOL, 'n' => 382, 'i' => Factor::V_FIRST_YES],
//    7149 => ['t' => Factor::F_I_BOOL, 'n' => 382, 'i' => Factor::V_FIRST_NO],
//
//    7154 => ['t' => Factor::F_I_BOOL, 'n' => 382, 'i' => Factor::V_SECOND_YES],
//    7155 => ['t' => Factor::F_I_BOOL, 'n' => 382, 'i' => Factor::V_SECOND_NO],

    /**
     * Все периоды > %pt
     */
//    7151 => ['t' => Factor::F_I_BOOL, 'n' => 383, 'i' => Factor::V_FIRST_YES],
//    7152 => ['t' => Factor::F_I_BOOL, 'n' => 383, 'i' => Factor::V_FIRST_NO],
//
//    7157 => ['t' => Factor::F_I_BOOL, 'n' => 383, 'i' => Factor::V_SECOND_YES],
//    7158 => ['t' => Factor::F_I_BOOL, 'n' => 383, 'i' => Factor::V_SECOND_NO],

    /**
     * Не проиграет ни одного периода
     */
//    2600 => ['t' => Factor::F_I_BOOL, 'n' => 64, 'i' => Factor::V_FIRST_YES],
//    2601 => ['t' => Factor::F_I_BOOL, 'n' => 64, 'i' => Factor::V_FIRST_NO],
//
//    2609 => ['t' => Factor::F_I_BOOL, 'n' => 64, 'i' => Factor::V_SECOND_YES],
//    2610 => ['t' => Factor::F_I_BOOL, 'n' => 64, 'i' => Factor::V_SECOND_NO],

    /**
     * Выиграет хотя бы 1 период
     */
//    2594 => ['t' => Factor::F_I_BOOL, 'n' => 80, 'i' => Factor::V_FIRST_YES],
//    2595 => ['t' => Factor::F_I_BOOL, 'n' => 80, 'i' => Factor::V_FIRST_NO],
//
//    2603 => ['t' => Factor::F_I_BOOL, 'n' => 80, 'i' => Factor::V_SECOND_YES],
//    2604 => ['t' => Factor::F_I_BOOL, 'n' => 80, 'i' => Factor::V_SECOND_NO],

    /**
     * Выиграет хотя бы 2 периода
     */
//    2597 => ['t' => Factor::F_I_BOOL, 'n' => 81, 'i' => Factor::V_FIRST_YES],
//    2598 => ['t' => Factor::F_I_BOOL, 'n' => 81, 'i' => Factor::V_FIRST_NO],
//
//    2606 => ['t' => Factor::F_I_BOOL, 'n' => 81, 'i' => Factor::V_SECOND_YES],
//    2607 => ['t' => Factor::F_I_BOOL, 'n' => 81, 'i' => Factor::V_SECOND_NO],

    /**
     * Выиграет 1-й тайм и не выиграет матч
     */
//    1061 => ['t' => Factor::F_I_BOOL, 'n' => 113, 'i' => Factor::V_FIRST_YES],

//    5137 => ['t' => Factor::F_I_BOOL, 'n' => 113, 'i' => Factor::V_FIRST_YES],
//    5138 => ['t' => Factor::F_I_BOOL, 'n' => 113, 'i' => Factor::V_FIRST_NO],
//
//    5140 => ['t' => Factor::F_I_BOOL, 'n' => 113, 'i' => Factor::V_SECOND_YES],
//    5141 => ['t' => Factor::F_I_BOOL, 'n' => 113, 'i' => Factor::V_SECOND_NO],

    /**
     * Забьет в каждом периоде
     */
//    1066 => ['t' => Factor::F_I_BOOL, 'n' => 85, 'i' => Factor::V_FIRST_YES],
//    1067 => ['t' => Factor::F_I_BOOL, 'n' => 85, 'i' => Factor::V_FIRST_NO],
//
//    904 => ['t' => Factor::F_I_BOOL, 'n' => 85, 'i' => Factor::V_SECOND_YES],
//    905 => ['t' => Factor::F_I_BOOL, 'n' => 85, 'i' => Factor::V_SECOND_NO],

    /**
     * Хотя бы одна не забьет и победа
     */
    4857 => [Factors::BODY, Factors::F_I_BOOL, 364, Factors::V_FIRST_YES, 'vn' => 22],
    4858 => [Factors::BODY, Factors::F_I_BOOL, 364, Factors::V_FIRST_NO, 'vn' => 23],

    4863 => [Factors::BODY, Factors::F_I_BOOL, 364, Factors::V_SECOND_YES, 'vn' => 24],
    4864 => [Factors::BODY, Factors::F_I_BOOL, 364, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Хотя бы одна не забьет и не проиграет
     */
    4866 => [Factors::BODY, Factors::F_I_BOOL, 366, Factors::V_FIRST_YES, 'vn' => 22],
    4867 => [Factors::BODY, Factors::F_I_BOOL, 366, Factors::V_FIRST_NO, 'vn' => 23],

    4872 => [Factors::BODY, Factors::F_I_BOOL, 366, Factors::V_SECOND_YES, 'vn' => 24],
    4873 => [Factors::BODY, Factors::F_I_BOOL, 366, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Обе забьют и не проиграет
     */
    4841 => [Factors::BODY, Factors::F_I_BOOL, 359, Factors::V_FIRST_YES, 'vn' => 22],
    4842 => [Factors::BODY, Factors::F_I_BOOL, 359, Factors::V_FIRST_NO, 'vn' => 23],

    4847 => [Factors::BODY, Factors::F_I_BOOL, 359, Factors::V_SECOND_YES, 'vn' => 24],
    4848 => [Factors::BODY, Factors::F_I_BOOL, 359, Factors::V_SECOND_NO, 'vn' => 25],

	/**
	 * Забьёт 2 гола подряд
	 */
	2343 => [Factors::BODY, Factors::F_I_BOOL, 117, Factors::V_FIRST_YES, 'vn' => 22],
	2344 => [Factors::BODY, Factors::F_I_BOOL, 117, Factors::V_FIRST_NO, 'vn' => 23],

	2349 => [Factors::BODY, Factors::F_I_BOOL, 117, Factors::V_SECOND_YES, 'vn' => 24],
	2350 => [Factors::BODY, Factors::F_I_BOOL, 117, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьёт 3 гола подряд
     */
    2346 => [Factors::BODY, Factors::F_I_BOOL, 118, Factors::V_FIRST_YES, 'vn' => 22],
    2347 => [Factors::BODY, Factors::F_I_BOOL, 118, Factors::V_FIRST_NO, 'vn' => 23],

    2352 => [Factors::BODY, Factors::F_I_BOOL, 118, Factors::V_SECOND_YES, 'vn' => 24],
    2353 => [Factors::BODY, Factors::F_I_BOOL, 118, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьёт 4 гола подряд
     */
//    2612 => ['t' => Factor::F_I_BOOL, 'n' => 119, 'i' => Factor::V_FIRST_YES],
//    2613 => ['t' => Factor::F_I_BOOL, 'n' => 119, 'i' => Factor::V_FIRST_NO],
//
//    2615 => ['t' => Factor::F_I_BOOL, 'n' => 119, 'i' => Factor::V_SECOND_YES],
//    2616 => ['t' => Factor::F_I_BOOL, 'n' => 119, 'i' => Factor::V_SECOND_NO],

    /**
     * Забьет?
     */
    4235 => [Factors::BODY, Factors::F_I_BOOL, 352, Factors::V_FIRST_YES, 'vn' => 22],
    4236 => [Factors::BODY, Factors::F_I_BOOL, 352, Factors::V_FIRST_NO, 'vn' => 23],

    4238 => [Factors::BODY, Factors::F_I_BOOL, 352, Factors::V_SECOND_YES, 'vn' => 24],
    4239 => [Factors::BODY, Factors::F_I_BOOL, 352, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Только команда забьет
     */
    4244 => [Factors::BODY, Factors::F_I_BOOL, 353, Factors::V_FIRST_YES, 'vn' => 22],
    4245 => [Factors::BODY, Factors::F_I_BOOL, 353, Factors::V_FIRST_NO, 'vn' => 23],

    4247 => [Factors::BODY, Factors::F_I_BOOL, 353, Factors::V_SECOND_YES, 'vn' => 24],
    4248 => [Factors::BODY, Factors::F_I_BOOL, 353, Factors::V_SECOND_NO, 'vn' => 25],

	/**
	 * В итоговом счете будет цифра
	 */
    3090 => [Factors::BODY, Factors::F_BOOL, 204, Factors::V_YES, 'vn' => 20],
    3091 => [Factors::BODY, Factors::F_BOOL, 204, Factors::V_NO, 'vn' => 21],

    3093 => [Factors::BODY, Factors::F_BOOL, 205, Factors::V_YES, 'vn' => 20],
    3094 => [Factors::BODY, Factors::F_BOOL, 205, Factors::V_NO, 'vn' => 21],

    3096 => [Factors::BODY, Factors::F_BOOL, 206, Factors::V_YES, 'vn' => 20],
    3097 => [Factors::BODY, Factors::F_BOOL, 206, Factors::V_NO, 'vn' => 21],

    3099 => [Factors::BODY, Factors::F_BOOL, 207, Factors::V_YES, 'vn' => 20],
    3100 => [Factors::BODY, Factors::F_BOOL, 207, Factors::V_NO, 'vn' => 21],

    3102 => [Factors::BODY, Factors::F_BOOL, 208, Factors::V_YES, 'vn' => 20],
    3103 => [Factors::BODY, Factors::F_BOOL, 208, Factors::V_NO, 'vn' => 21],

    7038 => [Factors::BODY, Factors::F_BOOL, 209, Factors::V_YES, 'vn' => 20],
    7039 => [Factors::BODY, Factors::F_BOOL, 209, Factors::V_NO, 'vn' => 21],

	/**
     * Победа и общий тотал < %pt
     */
    1050 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_FIRST_YES, 'vn' => 22],
    1906 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_FIRST_NO, 'vn' => 23],

    4925 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_FIRST_YES, 'vn' => 22],
    4926 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_FIRST_NO, 'vn' => 23],

    4928 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_FIRST_YES, 'vn' => 22],
    4929 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_FIRST_NO, 'vn' => 23],

    888  => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_SECOND_YES, 'vn' => 24],
    1951 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_SECOND_NO, 'vn' => 25],

    4949 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_SECOND_YES, 'vn' => 24],
    4950 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_SECOND_NO, 'vn' => 25],

    4952 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_SECOND_YES, 'vn' => 24],
    4953 => [Factors::BODY, Factors::F_I_BOOL, 325, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Победа и общий тотал > %pt
     */
    1051 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_FIRST_YES, 'vn' => 22],
    1908 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_FIRST_NO, 'vn' => 23],

    4931 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_FIRST_YES, 'vn' => 22],
    4932 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_FIRST_NO, 'vn' => 23],

    4934 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_FIRST_YES, 'vn' => 22],
    4935 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_FIRST_NO, 'vn' => 23],

    889  => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_SECOND_YES, 'vn' => 24],
    1953 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_SECOND_NO, 'vn' => 25],

    4955 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_SECOND_YES, 'vn' => 24],
    4956 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_SECOND_NO, 'vn' => 25],

    4958 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_SECOND_YES, 'vn' => 24],
    4959 => [Factors::BODY, Factors::F_I_BOOL, 324, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Не проиграет и общий тотал < %pt
     */
    1052 => [Factors::BODY, Factors::F_I_BOOL, 323, Factors::V_FIRST_YES, 'vn' => 22],
    1910 => [Factors::BODY, Factors::F_I_BOOL, 323, Factors::V_FIRST_NO, 'vn' => 23],

//    4937 => ['t' => Factor::F_I_BOOL, 'n' => 323, 'i' => Factor::V_FIRST_YES],
//    4938 => ['t' => Factor::F_I_BOOL, 'n' => 323, 'i' => Factor::V_FIRST_NO],

//    4940 => ['t' => Factor::F_I_BOOL, 'n' => 323, 'i' => Factor::V_FIRST_YES],
//    4941 => ['t' => Factor::F_I_BOOL, 'n' => 323, 'i' => Factor::V_FIRST_NO],

    890  => [Factors::BODY, Factors::F_I_BOOL, 323, Factors::V_SECOND_YES, 'vn' => 24],
    1955 => [Factors::BODY, Factors::F_I_BOOL, 323, Factors::V_SECOND_NO, 'vn' => 25],

//    4961 => ['t' => Factor::F_I_BOOL, 'n' => 323, 'i' => Factor::V_SECOND_YES],
//    4962 => ['t' => Factor::F_I_BOOL, 'n' => 323, 'i' => Factor::V_SECOND_NO],

//    4964 => ['t' => Factor::F_I_BOOL, 'n' => 323, 'i' => Factor::V_SECOND_YES],
//    4965 => ['t' => Factor::F_I_BOOL, 'n' => 323, 'i' => Factor::V_SECOND_NO],

    /**
     * Не проиграет и общий тотал > %pt
     */
    1053 => [Factors::BODY, Factors::F_I_BOOL, 322, Factors::V_FIRST_YES, 'vn' => 22],
    1912 => [Factors::BODY, Factors::F_I_BOOL, 322, Factors::V_FIRST_NO, 'vn' => 23],

//    4943 => ['t' => Factor::F_I_BOOL, 'n' => 322, 'i' => Factor::V_FIRST_YES],
//    4944 => ['t' => Factor::F_I_BOOL, 'n' => 322, 'i' => Factor::V_FIRST_NO],

//    4946 => ['t' => Factor::F_I_BOOL, 'n' => 322, 'i' => Factor::V_FIRST_YES],
//    4947 => ['t' => Factor::F_I_BOOL, 'n' => 322, 'i' => Factor::V_FIRST_NO],

    891  => [Factors::BODY, Factors::F_I_BOOL, 322, Factors::V_SECOND_YES, 'vn' => 24],
    1957 => [Factors::BODY, Factors::F_I_BOOL, 322, Factors::V_SECOND_NO, 'vn' => 25],

//    4967 => ['t' => Factor::F_I_BOOL, 'n' => 322, 'i' => Factor::V_SECOND_YES],
//    4968 => ['t' => Factor::F_I_BOOL, 'n' => 322, 'i' => Factor::V_SECOND_NO],

//    4970 => ['t' => Factor::F_I_BOOL, 'n' => 322, 'i' => Factor::V_SECOND_YES],
//    4971 => ['t' => Factor::F_I_BOOL, 'n' => 322, 'i' => Factor::V_SECOND_NO],

    /**
     * Забьет 1-й гол и выиграет
     */
//    1063 => ['t' => Factor::F_I_BOOL, 'n' => 368, 'i' => Factor::V_FIRST_YES],

    /**
     * Забьет 1-й гол и не выиграет
     */
//    1064 => ['t' => Factor::F_I_BOOL, 'n' => 368, 'i' => Factor::V_FIRST_YES],

    /**
     * Забьет %pt гол и победит
     */
    4888 => [Factors::BODY, Factors::F_I_BOOL, 368, Factors::V_FIRST_YES, 'vn' => 22],
    4889 => [Factors::BODY, Factors::F_I_BOOL, 368, Factors::V_FIRST_NO, 'vn' => 23],

    4913 => [Factors::BODY, Factors::F_I_BOOL, 368, Factors::V_SECOND_YES, 'vn' => 24],
    4914 => [Factors::BODY, Factors::F_I_BOOL, 368, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьет %pt гол и ничья
     */
    4891 => [Factors::BODY, Factors::F_I_BOOL, 369, Factors::V_FIRST_YES, 'vn' => 22],
    4892 => [Factors::BODY, Factors::F_I_BOOL, 369, Factors::V_FIRST_NO, 'vn' => 23],

    4910 => [Factors::BODY, Factors::F_I_BOOL, 369, Factors::V_SECOND_YES, 'vn' => 24],
    4911 => [Factors::BODY, Factors::F_I_BOOL, 369, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьет %pt гол и 1X
     */
    4897 => [Factors::BODY, Factors::F_I_BOOL, 370, Factors::V_FIRST_YES, 'vn' => 22],
    4898 => [Factors::BODY, Factors::F_I_BOOL, 370, Factors::V_FIRST_NO, 'vn' => 23],

    4916 => [Factors::BODY, Factors::F_I_BOOL, 370, Factors::V_SECOND_YES, 'vn' => 24],
    4917 => [Factors::BODY, Factors::F_I_BOOL, 370, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьет %pt гол и 12
     */
    4900 => [Factors::BODY, Factors::F_I_BOOL, 371, Factors::V_FIRST_YES, 'vn' => 22],
    4901 => [Factors::BODY, Factors::F_I_BOOL, 371, Factors::V_FIRST_NO, 'vn' => 23],

    4919 => [Factors::BODY, Factors::F_I_BOOL, 371, Factors::V_SECOND_YES, 'vn' => 24],
    4920 => [Factors::BODY, Factors::F_I_BOOL, 371, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьет %pt гол и X2
     */
    4903 => [Factors::BODY, Factors::F_I_BOOL, 372, Factors::V_FIRST_YES, 'vn' => 22],
    4904 => [Factors::BODY, Factors::F_I_BOOL, 372, Factors::V_FIRST_NO, 'vn' => 23],

    4922 => [Factors::BODY, Factors::F_I_BOOL, 372, Factors::V_SECOND_YES, 'vn' => 24],
    4923 => [Factors::BODY, Factors::F_I_BOOL, 372, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьет и победа
     */
    5100 => [Factors::BODY, Factors::F_I_BOOL, 374, Factors::V_FIRST_YES, 'vn' => 22],
    5101 => [Factors::BODY, Factors::F_I_BOOL, 374, Factors::V_FIRST_NO, 'vn' => 23],

    5125 => [Factors::BODY, Factors::F_I_BOOL, 374, Factors::V_SECOND_YES, 'vn' => 24],
    5126 => [Factors::BODY, Factors::F_I_BOOL, 374, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьет и ничья
     */
    5103 => [Factors::BODY, Factors::F_I_BOOL, 375, Factors::V_FIRST_YES, 'vn' => 22],
    5104 => [Factors::BODY, Factors::F_I_BOOL, 375, Factors::V_FIRST_NO, 'vn' => 23],

    5122 => [Factors::BODY, Factors::F_I_BOOL, 375, Factors::V_SECOND_YES, 'vn' => 24],
    5123 => [Factors::BODY, Factors::F_I_BOOL, 375, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьет и 1X
     */
    5109 => [Factors::BODY, Factors::F_I_BOOL, 376, Factors::V_FIRST_YES, 'vn' => 22],
    5110 => [Factors::BODY, Factors::F_I_BOOL, 376, Factors::V_FIRST_NO, 'vn' => 23],

    5128 => [Factors::BODY, Factors::F_I_BOOL, 376, Factors::V_SECOND_YES, 'vn' => 24],
    5129 => [Factors::BODY, Factors::F_I_BOOL, 376, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьет и 12
     */
    5112 => [Factors::BODY, Factors::F_I_BOOL, 377, Factors::V_FIRST_YES, 'vn' => 22],
    5113 => [Factors::BODY, Factors::F_I_BOOL, 377, Factors::V_FIRST_NO, 'vn' => 23],

    5131 => [Factors::BODY, Factors::F_I_BOOL, 377, Factors::V_SECOND_YES, 'vn' => 24],
    5132 => [Factors::BODY, Factors::F_I_BOOL, 377, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Забьет и X2
     */
    5115 => [Factors::BODY, Factors::F_I_BOOL, 378, Factors::V_FIRST_YES, 'vn' => 22],
    5116 => [Factors::BODY, Factors::F_I_BOOL, 378, Factors::V_FIRST_NO, 'vn' => 23],

    5134 => [Factors::BODY, Factors::F_I_BOOL, 378, Factors::V_SECOND_YES, 'vn' => 24],
    5135 => [Factors::BODY, Factors::F_I_BOOL, 378, Factors::V_SECOND_NO, 'vn' => 25],

    /**
     * Обе половины < %pt
     */
//    1081 => ['t' => Factor::F_I_BOOL, 'n' => 214, 'i' => Factor::V_FIRST_YES],
//    1082 => ['t' => Factor::F_I_BOOL, 'n' => 214, 'i' => Factor::V_FIRST_NO],
//
    /**
     * Обе половины > %pt
     */
//    1083 => ['t' => Factor::F_I_BOOL, 'n' => 215, 'i' => Factor::V_FIRST_YES],
//    1084 => ['t' => Factor::F_I_BOOL, 'n' => 215, 'i' => Factor::V_FIRST_NO],
//
    /**
     * Выиграет обе половины
     */
//    1674 => ['t' => Factor::F_I_BOOL, 'n' => 216, 'i' => Factor::V_FIRST_YES],
//    1673 => ['t' => Factor::F_I_BOOL, 'n' => 216, 'i' => Factor::V_FIRST_NO],
//
//    1693 => ['t' => Factor::F_I_BOOL, 'n' => 216, 'i' => Factor::V_SECOND_YES],
//    1694 => ['t' => Factor::F_I_BOOL, 'n' => 216, 'i' => Factor::V_SECOND_NO],
//
    /**
     * Выиграет все четверти
     */
//    1060 => ['t' => Factor::F_I_BOOL, 'n' => 379, 'i' => Factor::V_FIRST_YES],

];
