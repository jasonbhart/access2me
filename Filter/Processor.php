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

    /**
     * @var Model\Filter[]
     */
    private $failedFilters = [];

    public function __construct($filters)
    {
        $this->filters = $filters;
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

        $serviceProfile = $map[$filter['type']];

        // return null if profile is not available
        if (!$serviceProfile) {
            return null;
        }

        return $this->getValue($serviceProfile, $filter['property']);
    }

    public function process($profile) {
        $status = true;
        $this->failedFilters = [];

        if (!isset($this->filters)) {
            return;
        }

        $filterTypes = Helper\Registry::getFilterTypes();

        foreach ($this->filters as $filter) {
            $type = $filterTypes[$filter['type']];
            $property = $type->properties[$filter['property']];

            // some profiles may return few values (Combined profile)
            // so convert value to array
            $value = (array)$this->getProfileValue($profile, $filter);

            $comparator = \Access2Me\Filter\ComparatorFactory::getInstance($property['type']);
            $result = false;
            foreach ($value as $val) {
                $result = $result || $comparator->compare($filter['method'], $val, $filter['value']);
            }

            if ($result === false) {
                $this->status = false;
                $this->failedFilters[] = $filter;
            }
        }
    }

    public function getFailedFilters()
    {
        $result = [];
        $filterTypes = Helper\Registry::getFilterTypes();

        foreach ($this->failedFilters as $filter) {
            $type = $filterTypes[$filter['type']];
            $property = $type->properties[$filter['property']];

            $comparator = \Access2Me\Filter\ComparatorFactory::getInstance($property['type']);
            $method = $comparator->methods[$filter['method']];

            $result[] = $type->name . ': ' . $property['name'] . ' ' . $method['description'] . ' ' . $filter['value'];
        }

        return $result;
    }
}
