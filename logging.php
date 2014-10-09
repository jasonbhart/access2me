<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Logging
{
    public static function logDBErrorAndExit($error)
    {
        die('An Error Occurred: ' . $error);
    }
    //--------------------------------------------------------------------------

    private static $logger;

    public static function getLogger()
    {
        if (self::$logger == null) {
            $logfile = __DIR__ . "/logs/app.log";
            $logger = new Logger('app');
            $logger->pushHandler(new StreamHandler($logfile));
        }
        
        return $logger;
    }
}
