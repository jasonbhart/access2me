<?php

namespace Access2Me\Filter\Type;

use Access2Me\Filter\ComparatorFactory;


class CommonType extends AbstractType
{
    public $name = 'Common';

    public $properties = [
        'fullName' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'full name'
        ]
    ];
}
