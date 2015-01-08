<?php

namespace Access2Me\Helper;


class Utils
{
    public static function isValidEmail($email)
    {
        return !$empty($email) && \ezcMailTools::validateEmailAddress($email);
    }

    public static function isValidDomain($domain)
    {
        return dns_get_record($domain) !== false;
    }
}
