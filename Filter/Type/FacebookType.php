<?php

namespace Access2Me\Filter\Type;

use Access2Me\Filter\ComparatorFactory;


class FacebookType extends AbstractType
{
    public $name = 'Facebook';

    public $properties = [
        'fullName' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'full name'
        ],
        'biography' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'biography'
        ],
        'email' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'email'
        ],
        'gender' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'gender'
        ],
        'location' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'location'
        ],
        'website' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'website'
        ]
    ];
}
