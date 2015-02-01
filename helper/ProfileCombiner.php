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

    public $linkedin;
    public $crunchBase;
    public $angelList;
    public $fullContact;

    /**
     * 
     * @param \Access2Me\Model\Profile\Profile[] $profiles 
     */
    public function __construct($profiles)
    {
        // some profiles can be requested directly for specific properties
        $map = [
            Service\Service::LINKEDIN => 'linkedin',
            Service\Service::CRUNCHBASE => 'crunchBase',
            Service\Service::ANGELLIST => 'angelList',
            Service\Service::FULLCONTACT => 'fullContact',
        ];

        foreach ($map as $sid => $name) {
            if (isset($profiles[$sid])) {
                $this->{$name} = $profiles[$sid];
            }
        }

        unset($profiles[Service\Service::CRUNCHBASE]);
        unset($profiles[Service\Service::ANGELLIST]);
        unset($profiles[Service\Service::FULLCONTACT]);

        // this profiles we use for combined properties
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
