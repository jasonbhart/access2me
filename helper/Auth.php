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

    public static function encodePassword($password)
    {
        return md5('bacon' . $password);
    }

    protected function getUser($username)
    {
        $sql = "SELECT `id`, `mailbox`, `email`, `name`, `username`, `password`, `gmail_access_token`, `gmail_refresh_token`"
                . " FROM `users` WHERE `username` = '" . $username . "' LIMIT 1;";
        $user = $this->db->getArray($sql);
        
        return $user !== false ? $user[0] : null;
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
        $user = $this->getUser($username);

        // check user ?
        if ($user === null) {
            throw new AuthException('Invalid username');
        }

        // check password
        $hash = self::encodePassword($password);
        if ($user['password'] != $hash) {
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

        session_destroy();        
        $_SESSION['user'] = $user;
    }

    public function isAuthenticated()
    {
        if (!isset($_COOKIE['a2muser']) || !isset($_COOKIE['a2mauth'])) {
            return false;
        }

        $user = $this->getLoggedUser();
        
        return $user['password'] === $_COOKIE['a2mauth'];
    }

    public function logout()
    {
        unset($_COOKIE['a2muser']);
        unset($_COOKIE['a2mauth']);
        setcookie('a2muser', null, -1);
        setcookie('a2mauth', null, -1);
        session_destroy();
    }

    public function getLoggedUser()
    {
        // user may not be fully authenticated (entered credentials in this session)
        $user = isset($_SESSION['user']) ? $_SESSION['user'] : $this->getUser($_COOKIE['a2muser']);
        if ($user != null) {
            $_SESSION['user'] = $user;
        }
        
        return $user;
    }
}
