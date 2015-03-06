<?php

namespace Access2Me\Helper;


class Validator
{
    public static function isValidUsername($username)
    {
        return preg_match('/^\w{3,}$/', $username);
    }

    public static function isValidFullname($fullname)
    {
        return preg_match('/^[\w ]{5,}$/', $fullname);
    }

    public static function isValidEmail($email)
    {
        return !empty($email) && \ezcMailTools::validateEmailAddress($email);
    }

    public static function isValidDomain($domain)
    {
        return dns_get_record($domain) !== false;
    }

    public static function isValidPassword($password)
    {
        return mb_strlen($password) >= 5;
    }
}
