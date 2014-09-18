<?php

class Filter
{

    public  $status = true;

    private $filters;
    private $contact;

    protected $tableName = 'filters';
    const     TABLE_NAME = 'filters';

    public function __construct($userId, $contact, Database $db) {
        if (!empty($contact)) {
            $this->contact = $contact;
        } else {
            return false;
        }

        $query = "SELECT * FROM `" . $this->tableName . "` WHERE `user_id` = '" . $userId . "'";
        $filters = $db->getArray($query);

        if (!empty($filters)) {
            $this->filters = $filters;
        } else {
            return false;
        }

        return true;
    }
    //--------------------------------------------------------------------------


    public function processFilters() {
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
            }
        }
    }
    //--------------------------------------------------------------------------


    private function mustBe($filter) {
        if (strtolower($filter['value']) != strtolower($this->contact[$filter['field']])) {
            return false;
        }

        return true;
    }
    //--------------------------------------------------------------------------


    private function mustNotBe($filter) {
        if (strtolower($filter['value']) == strtolower($this->contact[$filter['field']])) {
            return false;
        }

        return true;
    }
    //--------------------------------------------------------------------------


    private function mustBeGreater($filter) {
        if ($filter['value'] > $this->contact[$filter['field']]) {
            return false;
        }

        return true;
    }
    //--------------------------------------------------------------------------


    private function mustNotBeGreater($filter) {
        if ($filter['value'] < $this->contact[$filter['field']]) {
            return false;
        }

        return true;
    }
    //--------------------------------------------------------------------------
}