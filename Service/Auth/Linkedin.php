<?php

namespace Access2Me\Service\Auth;

use Access2Me\Helper;

/**
 * Linkedin manager. Sends requests and is responsible for handling Linkedin responses.
 *
 * Example:
 * $request = new Linkedin\UserAuthRequest(4);
 * $manager = new Linkedin($appConfig['services']['linkedin']);
 * $manager->requestAuth($request);
 *
 * @package Access2Me\Service\Auth
 */
class Linkedin
{
    /**
     * Service config
     * @var array
     */
    private $config;

    /**
     * @var AbstractHandler[]
     */
    private $handlers = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @param AbstractHandler $handler
     */
    public function addHandler(AbstractHandler $handler)
    {
        $this->handlers[] = $handler;
    }

    public function requestAuth($request)
    {
        $linkedin = new Helper\Linkedin($this->config);
        $state = md5(mt_rand());

        $_SESSION['linkedin'][$state] = serialize($request);

        $url = $linkedin->getLoginUrl($state, $this->config['callback_url']);
        header('Location: ' . $url);
    }

    public function processResponse($data)
    {
        // validate response data
        if (!isset($data['state'])) {
            throw new Linkedin\AuthException('Invalid state token');
        }

        if (isset($data['error'])) {
            $message = $data['error'] . (isset($data['error_description']) ? ': ' . $data['error_description'] : '');
            throw new Linkedin\AuthException($message);
        }

        if (!isset($data['code'])) {
            throw new Linkedin\AuthException('Invalid access code');
        }

        // get access token
        $linkedin = new Helper\Linkedin($this->config);
        $result = $linkedin->getAccessToken($data['code'], $this->config['callback_url']);

        $state = $data['state'];

        // get handler
        if (isset($_SESSION['linkedin'][$state])) {
            $request = unserialize($_SESSION['linkedin'][$state]);
            unset($_SESSION['linkedin'][$state]);

            // pass to a handler
            foreach ($this->handlers as $handler) {
                if ($handler->canHandle($request)) {
                    $handler->handle($request, $result);
                    break;
                }
            }

            throw new Linkedin\AuthException('Handler for ' . get_class($request) . ' doesn\'t exist');
        }

        throw new Linkedin\AuthException('Unsolicited response');
    }
}
