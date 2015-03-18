<?php

namespace Access2Me\Filter\Comparator;

use Access2Me\Filter\ComparatorType;


class NumericComparator
{
    const EQUALS = 1;
    const NOT_EQUALS = 2;
    const GREATER = 3;
    const NOT_GREATER = 4;
    const LESSER = 5;
    const NOT_LESSER = 6;

    public $methods = [
        self::EQUALS => [
            'description' => 'must be equal to'
        ],
        self::NOT_EQUALS => [
            'description' => 'must NOT be equal to'
        ],
        self::GREATER => [
            'description' => 'must be greater than'
        ],
        self::NOT_GREATER => [
            'description' => 'must NOT be greater than'
        ],
        self::LESSER => [
            'description' => 'must be lesser than'
        ],
        self::NOT_LESSER => [
            'description' => 'must NOT be lesser than'
        ]
    ];

    /**
     * @param $method
     * @param $value1 profile value
     * @param $value2 filter value
     * @return bool
     */
    public function compare($method, $value1, $value2)
    {
        if ($method == self::EQUALS) {
            return $this->equals($value1, $value2);
        } else if ($method == self::NOT_EQUALS) {
            return !$this->equals($value1, $value2);
        } else if ($method == self::GREATER) {
            return $this->greater($value1, $value2);
        } else if ($method == self::NOT_GREATER) {
            return !$this->greater($value1, $value2);
        } else if ($method == self::LESSER) {
            return $this->lesser($value1, $value2);
        } else if ($method == self::NOT_LESSER) {
            return !$this->lesser($value1, $value2);
        }

        return false;
    }

    public function equals($value1, $value2)
    {
        return $value1 == $value2;
    }

    public function greater($value1, $value2)
    {
        return $value1 > $value2;
    }

    public function lesser($value1, $value2)
    {
        return $value1 < $value2;
    }
}