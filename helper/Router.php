<?php

namespace Access2Me\Helper;


class Router
{
    protected $routes = [
        'home' => '/ui/index.php',
        'google_config' => '/ui/gmail-config.php',
        'gmail_settings' => '/ui/gmail-settings.php',
        'login' => '/ui/login.php',
        'logout' => [
            'url' => '/ui/login.php',
            'params' => [
                'action' => 'logout'
            ]
        ],
        'registration_success' => '/ui/registration_success.php',
        'sender_profile' => '/ui/sender_profile.php',
        'sender_verification' => '/verify.php',
        'sidebar_handler' => '/ui/sidebar_handler.php',
        'user_sender_manage' => '/ui/user_senders.php',
        'user_sender_enroll' => '/user_senders.php',
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

        $url = $this->appConfig['projectUrl'] . $url;

        return $url;
    }

    public function getUserSenderEnrollUrl($token, $userId, $email, $accessType)
    {
        $url = $this->getUrl(
            'user_sender_enroll',
            [
                'token' => $token,
                'uid' => $userId,
                'email' => $email,
                'access_type' => $accessType
            ]
        );
        
        return $url;
    }
}
