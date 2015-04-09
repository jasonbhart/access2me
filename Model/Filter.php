<?php

namespace Access2Me\Model;

class Filter
{
    private $id;

    /**
     * @var int
     */
    private $user_id;

    /**
     * One of \Access2Me\Filter\TypeFactory\* constants
     * ex: COMMON -> CommonType
     * @var int
     */
    private $type;

    /**
     * Property defined in class that provides filtering for type $type
     * ex: firstName
     * @var string
     */
    private $property;

    /**
     * Method's id described in Comparator class that $property uses
     * ex: EQUALS
     * @var int
     */
    private $method;

    private $value;


    public function getId()
    {
        return $this->id;
    }
    
    public function getUserId()
    {
        return $this->user_id;
    }
    
    public function  setUserId($userId)
    {
        $this->user_id = $userId;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setProperty($property)
    {
        $this->property = $property;
    }

    public function getMethod()
    {
        return $this->method;
    }
    
    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function getValue()
    {
        return $this->value;
    }
    
    public function setValue($value)
    {
        $this->value = $value;
    }
}
