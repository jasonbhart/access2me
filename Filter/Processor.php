<?php

namespace Access2Me\Filter;

use Access2Me\Helper;
use Access2Me\Model;


class Processor
{
    /**
     * @var Model\Filter[]
     */
    private $filters;

    private $typeFactory;
    private $comparatorFactory;

    /**
     * @todo
     * @var array ['filter' => Model\Filter, 'value' => .., 'status' => boolean];
     */
    private $stat = [];

    public function __construct($filters)
    {
        $this->filters = $filters;
        $this->typeFactory = Helper\Registry::getFilterTypeFactory();
        $this->comparatorFactory = Helper\Registry::getFilterComparatorFactory();
    }

    protected function getValue($profile, $property)
    {
        if (is_array($profile)) {
            return $profile[$property];
        } else if (is_object($profile)) {
            return $profile->{$property};
        }

        throw new \Exception('Can\'t get property on non-object/array');
    }

    protected function getProfileValue($profile, $filter)
    {
        $map = [
            \Access2Me\Filter\TypeFactory::COMMON => $profile,
            \Access2Me\Filter\TypeFactory::LINKEDIN => $profile->linkedin,
            \Access2Me\Filter\TypeFactory::FACEBOOK => $profile->facebook,
            \Access2Me\Filter\TypeFactory::TWITTER => $profile->twitter
        ];

        $serviceProfile = $map[$filter->getType()];

        // return null if profile is not available
        if (!$serviceProfile) {
            return null;
        }

        return $this->getValue($serviceProfile, $filter->getProperty());
    }

    public function process($profile) {
        $status = true;
        $filters = [];

        foreach ($this->filters as $filter) {
            $type = $this->typeFactory->create($filter->getType());
            $property = $type->properties[$filter->getProperty()];

            // some profiles may return few values (Combined profile)
            // so convert value to array
            $value = (array)$this->getProfileValue($profile, $filter);

            $comparator = $this->comparatorFactory->create($property['type']);
            $result = false;
            foreach ($value as $val) {
                $result = $result || $comparator->compare($filter->getMethod(), $val, $filter->getValue());
            }

            // collect statistics
            $filters[] = [
                'type' => $type,
                'comparator' => $comparator,
                'filter' => $filter,
                'value' => $value,
                'status' => $result
            ];

            $status = $status && $result;
        }

        $this->stat = [
            'filters' => $filters,
            'status' => $status
        ];
        
        return $status;
    }

    public function getStat()
    {
        return $this->stat;
    }
}
