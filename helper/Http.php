<?php

namespace Access2Me\Helper;

class Http403Exception extends \Exception {}
class Http404Exception extends \Exception {}
class Http500Exception extends \Exception {}

class Http
{
    public static function generate403()
    {
        header('HTTP/1.0 403 Access Denied');
        exit;
    }

    public static function generate404()
    {
        header('HTTP/1.0 404 Not Found');
        exit;
    }

    public static function generate500()
    {
        header('HTTP/1.0 500 Internal Server Error');
        exit;
    }

    public static function jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
