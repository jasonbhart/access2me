<?php

class Config_Database
{

    private $credentials = array(
        'host'     => 'localhost',
        'port'     => NULL,
        'database' => 'a2m',
        'user'     => 'a2m',
        'pass'     => 'a2m123'
    );

    public function getHost()
    {
        return $this->credentials['host'];
    }
    //--------------------------------------------------------------------------


    public function getPort()
    {
        return $this->credentials['port'];
    }
    //--------------------------------------------------------------------------


    public function getDatabase()
    {
        return $this->credentials['database'];
    }
    //--------------------------------------------------------------------------


    public function getUser()
    {
        return $this->credentials['user'];
    }
    //--------------------------------------------------------------------------


    public function getPass()
    {
        return $this->credentials['pass'];
    }
    //--------------------------------------------------------------------------
}
