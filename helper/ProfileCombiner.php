<?php

namespace Access2Me\Helper;

use Access2Me\Service;

/**
 * Combines values of the same properties from different profiles
 * TODO: This class needs to be reworked
 */
class ProfileCombiner
{
    /**
     * Contains available profiles with a keys holding service ids
     * @var \Access2Me\Model\Profile\Profile[]
     */
    protected $profiles = array();

    public $crunchBase;
    public $angelList;

    /**
     * 
     * @param \Access2Me\Model\Profile\Profile[] $profiles 
     */
    public function __construct($profiles)
    {
        if (isset($profiles[Service\Service::CRUNCHBASE])) {
            $this->crunchBase = $profiles[Service\Service::CRUNCHBASE];
            unset($profiles[Service\Service::CRUNCHBASE]);
        } else if (isset($profiles[Service\Service::ANGELLIST])) {
            $this->angelList = $profiles[Service\Service::ANGELLIST];
            unset($profiles[Service\Service::ANGELLIST]);
        }

        $this->profiles = $profiles;
    }

    /**
     * @param type $name
     * @return type
     * @see getProperty()
     */
    public function __get($name)
    {
        return $this->getProperty($name);
    }

    /**
     * Returns list of all values for given property name
     * 
     * @param string $name
     * @return array array(serviceId => $value, ...)
     * @throws \Exception
     */
    public function getProperty($name)
    {
        $result = array();
        foreach ($this->profiles as $serviceId => $profile) {
            if (!$profile) {
                continue;
            }

            if (!property_exists($profile, $name)) {
                throw new \Exception('Unknown property ' . $name);
            }

            if (!empty($profile->$name)) {
                $result[$serviceId] = $profile->$name;
            }
        }
        
        return $result;
    }

    /**
     * Returns first value from the list of all values for given property name
     * 
     * @param string $name
     * @return string
     */
    public function getFirst($name)
    {
        $values = $this->getProperty($name);
        
        return array_shift($values);
    }
}
