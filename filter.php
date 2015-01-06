<?php

use Access2Me\Model\Profile;
use Access2Me\Helper;

class Filter
{

    public  $status = true;

    private $filters;
    private $contact;

    protected $tableName = 'filters';
    const     TABLE_NAME = 'filters';

    const EQUAL_TO = 1;
    const NOT_EQUAL_TO = 2;
    const GREATER_THAN = 3;
    const NOT_GREATER_THAN = 4;

    private static $types = array(
        self::EQUAL_TO,
        self::NOT_EQUAL_TO,
        self::GREATER_THAN,
        self::NOT_GREATER_THAN
    );

    private $failedFilters = [];

    public function __construct($userId, $contact, Database $db) {
        if (!empty($contact)) {
            $this->contact = $contact;
        } else {
            return false;
        }

        $query = "SELECT * FROM `" . $this->tableName . "` WHERE `user_id` = '" . $userId . "'";
        $filters = $db->getArray($query);

        $this->filters = !empty($filters) ? $filters : array();
    }
    //--------------------------------------------------------------------------


    public function processFilters() {
        $this->failedFilters = [];
        

        if (isset($this->filters)) {
            foreach ($this->filters AS $filter) {
                switch ($filter['type']) {
                    case "1":
                        $response = $this->mustBe($filter);
                        break;
                    case "2":
                        $response = $this->mustNotBe($filter);
                        break;
                    case "3":
                        $response = $this->mustBeGreater($filter);
                        break;
                    case "4":
                        $response = $this->mustNotBeGreater($filter);
                        break;
                }

                if (isset($response) && $response === false) {
                    $this->status = false;
                    $this->failedFilters[] = $filter;
                }
            }
        }
    }
    //--------------------------------------------------------------------------

    /**
     * Apply filter func to each value in certain field
     * (fields can contain multiple value - ProfileCombiner)
     * All fields must match
     * 
     * @param \Access2Me\Model\Profile\Profile|\Access2Me\Helper\ProfileCombiner $filter
     * @param callable $condition
     */
    private function applyFilter($field, $condition)
    {
        $values = (array)$this->contact->{$field};
 
        $result = true;
        foreach ($values as $value) {
            $result = $result && call_user_func($condition, $value);
        }
        
        return $result;
    }

    private function mustBe($filter) {
        $filterValue = strtolower($filter['value']);
        return $this->applyFilter(
            $filter['field'],
            function($value) use($filterValue) {
                return $filterValue == strtolower($value);
            }
        );
    }
    //--------------------------------------------------------------------------


    private function mustNotBe($filter) {
        $filterValue = strtolower($filter['value']);
        return $this->applyFilter(
            $filter['field'],
            function($value) use($filterValue) {
                return $filterValue != strtolower($value);
            }
        );
    }
    //--------------------------------------------------------------------------


    private function mustBeGreater($filter) {
        return $this->applyFilter(
            $filter['field'],
            function($value) use($filter) {
                return $filter['value'] > $value;
            }
        );
    }
    //--------------------------------------------------------------------------


    private function mustNotBeGreater($filter) {
        return $this->applyFilter(
            $filter['field'],
            function($value) use($filter) {
                return $filter['value'] < $value;
            }
        );
    }
    //--------------------------------------------------------------------------


    static public function getFiltersByUserId($userId, Database $db) {
        if (!$db) {
            return false;
        }

        $query = "SELECT * FROM `" . self::TABLE_NAME . "` WHERE `user_id` = '" . $userId . "' ORDER BY `id` DESC";
        $filters = $db->getArray($query);

        if (!empty($filters)) {
            return $filters;
        } else {
            return false;
        }
    }
    //--------------------------------------------------------------------------

    
    static public function getFilterById($id, Database $db) {
        if (!$db) {
            return false;
        }

        $query = "SELECT * FROM `" . self::TABLE_NAME . "` WHERE `id` = ?";
        $filters = $db->getArray($query, array($id));

        if (!empty($filters)) {
            return $filters[0];
        } else {
            return null;
        }
    }
    //--------------------------------------------------------------------------

    static public function delete($id, Database $db) {
        if (!$db) {
            return false;
        }

        $query = "DELETE FROM `" . self::TABLE_NAME . "` WHERE `id` = ?";
        return $db->execute($query, array($id));
    }
    //--------------------------------------------------------------------------


    static public function getConditionNameByType($type) {
        switch ($type) {
            case '1':
                return 'Must be equal to';
            break;
            case '2':
                return 'Must NOT be equal to';
            break;
            case '3':
                return 'Must be greater than';
            break;
            case '4':
                return 'Must NOT be greater than';
            break;
        }
    }
    //--------------------------------------------------------------------------

    public static function getTypes()
    {
        return self::$types;
    }

    public static function getConditions()
    {
        $conditions = array();
        foreach (self::$types as $type) {
            $conditions[$type] = self::getConditionNameByType($type);
        }
        
        return $conditions;
    }

    /**
     * Get profile properties that have @displayName
     * 
     * @return array
     */
    public static function getFilterableFields()
    {
        $refl = new \ReflectionClass('\Access2Me\Model\Profile\Profile');
        $fields = array();
        foreach ($refl->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $displayName = Profile\ProfileRepository::getDisplayName($prop);
            if ($displayName) {
                $fields[$prop->getName()] = $displayName;
            }
        }

        return $fields;
    }

    public function getFailedFilters()
    {
        $result = [];
        $descr = self::getFilterableFields();
        foreach ($this->failedFilters as $filter) {
            $result[] = $descr[$filter['field']]
                . ' ' . mb_strtolower(self::getConditionNameByType($filter['type']))
                . ' ' . $filter['value'];
        }
        
        return $result;
    }
}
