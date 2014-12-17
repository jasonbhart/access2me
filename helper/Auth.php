<?php

namespace Access2Me\Helper;

class AuthException extends \Exception {}

class Auth
{
    /**
     * @var \Database
     */
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
                . " FROM `users` WHERE `username` = ? LIMIT 1;";
        $user = $this->db->getArray($sql, [$username]);

        return $user !== false ? $user[0] : null;
    }

    protected function checkPassword($user, $passwordHash)
    {
        return $user != null && $user['password'] == $passwordHash;
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
        $hash = self::encodePassword($password);

        // check user and passowrd
        if (!$this->checkPassword($user, $hash)) {
            throw new AuthException('Invalid credentials');
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

        try {
            $user = $this->getLoggedUser();
            return true;
        } catch (AuthException $ex) {
            return false;
        }
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
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        } else {
            $user = $this->getUser($_COOKIE['a2muser']);

            if (!$this->checkPassword($user, $_COOKIE['a2mauth'])) {
                throw new AuthException('Not authenticated');
            }

            $_SESSION['user'] = $user;
        }
        
        return $user;
    }
}
