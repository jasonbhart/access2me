<?php

namespace Access2Me\Helper;

class FlashMessage {
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

    public static function toHTML() {
        $cssClasses = [
            self::SUCCESS => 'alert-success',
            self::INFO => 'alert-info',
            self::ERROR => 'alert-danger'        
        ];

        $result = [];

        if (self::hasMessages()) {
            foreach (self::getAll() as $msg) {
                $cssClass = $cssClasses[$msg['type']];
                $result[] = <<<"EOT"
<div class="alert alert-dismissable {$cssClass}">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <div>{$msg['message']}</div>
</div>
EOT;
            }
        }
        
        return implode('', $result);
    }
}
