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

    protected function getValue($data, $property)
    {
        if (is_array($data)) {
            return $data[$property];
        } else if (is_object($data)) {
            return $data->{$property};
        }

        throw new \Exception('Can\'t get property on non-object/array');
    }

    protected function getProfileValue($filter)
    {
        $map = [
            \Access2Me\Filter\TypeFactory::COMMON => $this->contact,
            \Access2Me\Filter\TypeFactory::LINKEDIN => $this->contact->linkedin,
            \Access2Me\Filter\TypeFactory::FACEBOOK => $this->contact->facebook,
            \Access2Me\Filter\TypeFactory::TWITTER => $this->contact->twitter
        ];

        return $this->getValue($map[$filter['type']], $filter['property']);
    }

    public function processFilters() {
        $this->status = true;
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
            $value = (array)$this->getProfileValue($filter);

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
