<?php

namespace Access2Me\Helper;

/**
 * Combines values of the same properties from different profiles
 */
class ProfileCombiner
{
    /**
     * Contains available profiles with a keys holding service ids
     * @var \Access2Me\Model\Profile\Profile[]
     */
    protected $profiles = array();

    /**
     * 
     * @param \Access2Me\Model\Profile\Profile[] $profiles 
     */
    public function __construct($profiles)
    {
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
