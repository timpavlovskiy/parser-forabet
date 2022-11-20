<?php

/**
 * Class Margin
 */
class Margin
{
    const MODE_DEFAULT = 0;
    const MODE_HIGH = 1;
    const MODE_LOW = 2;

    /**
     * @var array
     */
    private static $_margin_live;

    /**
     * @var array
     */
    private static $_margin_line;

    /**
     * @var float
     */
    private static $_margin_step;

    /**
     * @var int
     */
    private static $_margin_mode;

    /**
     * @var float
     */
    private static $_maximum_margin;

    /**
     * @var float
     */
    private static $_minimal_margin;

    /**
     * @var float
     */
    private $_event_margin;

    private $_margins  = [];

    /**
     *
     */
    private static $_default_markets = [];

    private $_currentDefault = [];
    private $_currentHigh = [];
    private $_currentLow = [];

	/**
	 * @param array $markets
	 */
	public static function setDefaults(array $markets)
	{
        self::$_default_markets = $markets;
	}

	public static function setConstraint($constraints)
    {
        self::$_margin_live = $constraints['live'];
        self::$_margin_line = $constraints['line'];
        self::$_margin_step = $constraints['step'] ?? 2.5;
        self::$_margin_mode = $constraints['mode'] ?? 1;
    }

	/**
	 * @param array $margins
	 * @param array $event
	 *
	 * @return Margin
	 */
    public function setMargins(array $margins, $event): Margin
	{
        $this->_setMinAndMaxMargin($event);
        $this->_setEventMargin($event);

		$this->_margins = $margins;

        foreach (self::$_default_markets as $id => $item ) {
			if ( isset($margins[$id]) ) {
				$item = $margins[$id];
			}

			$margin = $item['margin'];

			if ( $item['is_addition'] ) {
				$margin += $this->_event_margin;
			}

			if ( $margin < $this->_event_margin ) {
                $margin = $this->_event_margin;
			}

            $marginHigh = $margin + self::$_margin_step;
            $marginLow = $margin - self::$_margin_step;

            $this->_currentDefault[$id] = $this->checkMargin($margin);
            $this->_currentHigh[$id] = $this->checkMargin($marginHigh);
            $this->_currentLow[$id] = $this->checkMargin($marginLow);
		}

		return $this;
	}

	public function getForMarket(int $name, int $mode): float
	{
		if ( $name === 1 || $name === 2 ) {
			$name = 1;
		}

		$margin = 0;

		switch ( $mode ) {
            case self::MODE_DEFAULT:
                $margin = $this->_currentDefault[$name] ?? $this->_event_margin;
                break;
            case self::MODE_HIGH:
                $margin = $this->_currentHigh[$name] ?? $this->_event_margin;
                break;
            case self::MODE_LOW:
                $margin = $this->_currentLow[$name] ?? $this->_event_margin;
                break;
        }

		return $margin;
	}

    public function getMarginMode(): int
    {
        return self::$_margin_mode;
    }

	public function getMarginStep()
    {
        return self::$_margin_step;
    }

    public function isDisabled(int $name): bool
    {
        return $this->_margins[$name]['is_disabled'] ?? false;
    }

    public function checkMargin(float $margin): float
    {
        if( $margin > self::$_maximum_margin ) {
            return self::$_maximum_margin;
        }

        if( $margin < self::$_minimal_margin ) {
            return self::$_minimal_margin;
        }

        return $margin;
    }

    /**
     * @param array $event
     * @return Margin
     */
    private function _setEventMargin($event): Margin
    {
        $this->_event_margin = $this->checkMargin($event['margin']);

        return $this;
    }

    /**
     * @param array $event
     * @return Margin
     */
    private function _setMinAndMaxMargin($event): Margin
    {
        $constraint = [];

        if( $event['type'] === 1 ) {
            $constraint = self::$_margin_live;
        }

        if( $event['type'] === 0 ) {
            $constraint = self::$_margin_line;
        }

        self::$_maximum_margin = $constraint['max'] ?? 12;
        self::$_minimal_margin = $constraint['min'] ?? 3;

        return $this;
    }
}