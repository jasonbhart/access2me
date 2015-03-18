<?php

namespace Access2Me\Filter\Type;

use Access2Me\Filter\ComparatorFactory;


class TwitterType extends AbstractType
{
    public $name = 'Twitter';

    public $properties = [
        'fullName' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'full name'
        ],
        'location' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'location'
        ],
        'summary' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'summary'
        ]
    ];
}
