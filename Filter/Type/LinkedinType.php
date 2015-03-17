<?php

namespace Access2Me\Filter\Type;

use Access2Me\Filter\ComparatorFactory;


class LinkedinType extends AbstractType
{
    public $name = 'Linkedin';

    public $properties = [
        'firstName' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'first name'
        ],
        'lastName' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'last name'
        ],
        'email' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'email'
        ],
        'headline' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'headline'
        ],
        'location' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'location'
        ],
        'industry' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'industry'
        ],
        'summary' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'summary'
        ],
        'specialties' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'specialties'
        ],
        'interests' => [
            'type' => ComparatorFactory::TEXT,
            'name' => 'interests'
        ],
        'connections' => [
            'type' => ComparatorFactory::NUMERIC,
            'name' => 'connections'
        ]
    ];
}
