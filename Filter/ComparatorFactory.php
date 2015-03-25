<?php

namespace Access2Me\Filter;


class ComparatorFactory
{
    const NUMERIC = 1;
    const TEXT = 2;

    public function create($type)
    {
        if ($type == self::NUMERIC) {
            return new Comparator\NumericComparator();
        } else if ($type == self::TEXT) {
            return new Comparator\TextComparator();
        }
    }
}
