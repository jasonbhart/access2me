<?php

class Database
{
    protected $link;
    protected $connected;
    protected $tableName;

    public function __construct()
    {
        $credentials = new Config_Database();

        try {
            $this->link = new PDO(
                'mysql:host=' . $credentials->getHost() . ';dbname=' . $credentials->getDatabase(),
                $credentials->getUser(),
                $credentials->getPass(),
                array(
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)
            );
        } catch (PDOException $e) {
            Logging::logDBErrorAndExit($e->getMessage());
        }
    }
    //--------------------------------------------------------------------------

    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->link;
    }
    //--------------------------------------------------------------------------


    public function insert($tableName, $columns, $data, $ignore = false)
    {
        $statement  = "INSERT";

        if ($ignore) {
            $statement .= " IGNORE";
        }

        $statement .= " INTO `" . $tableName . "`";
        $statement .= " (";

        for ($x = 0; $x < sizeof($columns); $x++) {
            if ($x > 0) { $statement .= ', '; }
            $statement .= '`' . $columns[$x] . '`';
        }

        $statement .= ")";
        $statement .= " values (";

        for ($x = 0; $x < sizeof($data); $x++) {
            if ($x > 0) { $statement .= ', '; }
            $statement .= "?";
        }

        $statement .= ")";

        try {
            $insert = $this->link->prepare($statement);
            $insert->execute($data);
            return $this->link->lastInsertId();
        } catch (PDOException $e) {
            Logging::logDBErrorAndExit($e->getMessage());
        }
    }
    //--------------------------------------------------------------------------


    public function updateOne($tableName, $column, $data, $where, $condition)
    {
        $statement  = "UPDATE";

        $statement .= " `" . $tableName . "`";
        $statement .= " SET `";

        $statement .= $column . "`";
        $statement .= ' = ';
        $statement .= "?";

        $statement .= " WHERE `" . $where . "` = ?";

        try {
            $update = $this->link->prepare($statement);
            $update->execute(array($data, $condition));
        } catch (PDOException $e) {
            Logging::logDBErrorAndExit($e->getMessage());
        }
    }
    //--------------------------------------------------------------------------

    public function update($tableName, $columns, $data, $where = null, $condition=array())
    {
        $statement  = 'UPDATE `' . $tableName . '`';
        // generate pairs column = ? for SET
        $fields = array_map(
            function ($f, $v) {
                return sprintf('`%s` = ?', $f, $v);
            },
            $columns,
            array_fill(0, count($columns), '?')
        );
        $statement .= ' SET ' . implode(', ', $fields);
        
        if ($where) {
            $statement .= ' WHERE ' . $where;
        }
             
        try {
            $update = $this->link->prepare($statement);
            $update->execute(array_merge($data, $condition));
        } catch (PDOException $e) {
            Logging::logDBErrorAndExit($e->getMessage());
        }
    }
    //--------------------------------------------------------------------------


    public function getArray($statement, $data = array())
    {
        try {
            $select = $this->link->prepare($statement);
            $select->execute($data);
            $results = $select->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logging::logDBErrorAndExit($e->getMessage());
        }

        if (!empty($results)) {
            return $results;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    public function execute($statement, $data = array())
    {
        try {
            $delete = $this->link->prepare($statement);
            if ($delete->execute($data)) {
                return $delete->rowCount();
            }
        } catch (PDOException $e) {
            Logging::logDBErrorAndExit($e->getMessage());
        }

        return false;
    }
    //--------------------------------------------------------------------------
}
