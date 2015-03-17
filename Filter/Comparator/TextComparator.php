<?php

namespace Access2Me\Filter\Comparator;


class TextComparator
{
    const EQUALS = 1;
    const NOT_EQUALS = 2;
    const CONTAINS = 3;
    const NOT_CONTAINS = 4;

    public $methods = [
        self::EQUALS => [
            'description' => 'must be equal to'
        ],
        self::NOT_EQUALS => [
            'description' => 'must NOT be equal to'
        ],
        self::CONTAINS => [
            'description' => 'must contain'
        ],
        self::NOT_CONTAINS => [
            'description' => 'must NOT contain'
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
        } else if ($method == self::CONTAINS) {
            return $this->contains($value1, $value2);
        } else if ($method == self::NOT_CONTAINS) {
            return !$this->contains($value1, $value2);
        }

        return false;
    }

    public function equals($value1, $value2)
    {
        return $value1 == $value2;
    }

    public function contains($value1, $value2)
    {
        return mb_strpos($value1, $value2) >= 0;
    }
}
