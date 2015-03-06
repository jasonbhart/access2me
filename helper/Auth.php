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

    /**
     * @todo Pass UserRepository via constructor
     * @param $username
     * @return null
     */
    protected function getUser($username)
    {
        $repo = new \Access2Me\Model\UserRepository($this->db);
        $user = $repo->getByUsername($username);
        return $user;
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
        $_SESSION['username'] = $user['username'];
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
        if (isset($_SESSION['username'])) {
            return $this->getUser($_SESSION['username']);
        }

        // user may not be fully authenticated
        // not entered credentials in this session but has cookies
        $user = $this->getUser($_COOKIE['a2muser']);

        if (!$this->checkPassword($user, $_COOKIE['a2mauth'])) {
            throw new AuthException('Not authenticated');
        }

        $_SESSION['username'] = $user['username'];
        
        return $user;
    }
}
