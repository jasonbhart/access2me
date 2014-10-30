<?php

namespace Access2Me\Helper;

class AuthException extends \Exception {}

class Auth
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function encodePassword($password)
    {
        return md5('bacon' . $password);
    }

    protected function getPassword($username)
    {
        $sql = "SELECT `password` FROM `users` WHERE `username` = '" . $username . "' LIMIT 1;";
        $password = $this->db->getArray($sql);
        
        return $password !== false ? $password[0]['password'] : false;
    }

    /**
     * Authenticates user and sets auth cookies
     * 
     * @param string $username
     * @param string $password
     * @param boolean $remember
     * @throws AuthException
     */
    public function login($username, $password, $remember = false)
    {
        $savedPw = $this->getPassword($username);

        // check user ?
        if ($savedPw === false) {
            throw new AuthException('Invalid username');
        }

        // check password
        $hash = self::encodePassword($password);
        if ($savedPw != $hash) {
            throw new AuthException('Invalid password');
        }

        // valid
        if ($remember) {
            $expire = time() + (60 * 60 * 24 * 30);
        } else {
            $expire = time() + 3600;
        }

        setcookie('a2muser', $username, $expire);
        setcookie('a2mauth', $hash, $expire);
    }

    public function isAuthenticated()
    {
        if (!isset($_COOKIE['a2muser']) || !isset($_COOKIE['a2mauth'])) {
            return false;
        }

        return $this->getPassword($_COOKIE['a2muser']) === $_COOKIE['a2mauth'];
    }

    public function logout()
    {
        unset($_COOKIE['a2muser']);
        unset($_COOKIE['a2mauth']);
        setcookie('a2muser', null, -1);
        setcookie('a2mauth', null, -1);
    }
}
