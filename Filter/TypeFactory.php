<?php

namespace Access2Me\Filter;


class TypeFactory
{
    const COMMON = 1;
    const LINKEDIN = 2;
    const FACEBOOK = 3;
    const TWITTER = 4;

    public $types = [
        self::COMMON,
        self::LINKEDIN,
        self::FACEBOOK,
        self::TWITTER
    ];

    public function create($type)
    {
        if ($type == self::COMMON) {
            return new Type\CommonType();
        } else if ($type == self::LINKEDIN) {
            return new Type\LinkedinType();
        } else if ($type == self::FACEBOOK) {
            return new Type\FacebookType();
        } else if ($type == self::TWITTER) {
            return new Type\TwitterType();
        }
    }
}
