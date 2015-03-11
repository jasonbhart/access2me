<?php

namespace Access2Me\Helper;

class FlashMessages {
    private static $storageKey = 'flash_messages';

    const SUCCESS = 1;
    const INFO = 2;
    const ERROR = 3;

    public static function add($message, $type) {
        if (!isset($_SESSION[self::$storageKey])) {
            $_SESSION[self::$storageKey] = [];
        }

        $_SESSION[self::$storageKey][] = [
            'message' => $message,
            'type' => $type
        ];
    }

    public static function hasMessages() {
        return isset($_SESSION[self::$storageKey]) && count($_SESSION[self::$storageKey]) > 0;
    }

    public static function getAll($type=-1, $clear=true) {
        if (isset($_SESSION[self::$storageKey])) {
            $messages = $_SESSION[self::$storageKey];
            unset($_SESSION[self::$storageKey]);
            return $messages;
        }
        
        return [];
    }
}
