<?php

namespace Access2Me\Helper;


class Router
{
    protected $routes = [
        'home' => '/ui/index.php',
        'gmail_settings' => '/ui/gmail-config.php',
        'registration_success' => '/ui/registration_success.php',
        'sender_verification' => '/verify.php'
    ];

    protected $appConfig;

    public function __construct($appConfig)
    {
        $this->appConfig = $appConfig;
    }

    public function getUrl($routeName, $params = [])
    {
        if (!isset($this->routes[$routeName])) {
            throw new \InvalidArgumentException('routeName: ' . $routeName);
        }

        $route = $this->routes[$routeName];

        // prepare url
        if (is_array($route)) {
            $url = $route['url'];

            // merge params with default params
            if (isset($route['params'])) {
                $params = array_merge($route['params'], $params);
            }
        } else {
            $url = $route;
        }

        // append query
        $query = http_build_query($params);
        if ($query) {
            $url .= '?' . $query;
        }

        $url = $this->appConfig['siteUrl'] . $url;

        return $url;
    }
}
