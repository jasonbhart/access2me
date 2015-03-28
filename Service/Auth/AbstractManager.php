<?php

namespace Access2Me\Service\Auth;

/**
 * Auth manager. Sends requests and is responsible for handling responses.
 * @package Access2Me\Service\Auth
 */
abstract class AbstractManager
{
    /**
     * Service config
     * @var array
     */
    protected $config;

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

    protected function storeRequest($state, $serviceRequest)
    {
        $_SESSION[get_class($this)][$state] = serialize($serviceRequest);
    }

    protected function handleResult($state, $result)
    {
        // get handler
        $key = get_class($this);
        if (isset($_SESSION[$key][$state])) {
            $request = unserialize($_SESSION[$key][$state]);
            unset($_SESSION[$key][$state]);

            // pass to a handler
            foreach ($this->handlers as $handler) {
                if ($handler->canHandle($request)) {
                    $handler->handle($request, $result);
                    break;
                }
            }

            throw new AuthException('Handler for ' . get_class($request) . ' doesn\'t exist');
        }

        throw new AuthException('Unsolicited response');
    }

    abstract public function requestAuth(AbstractRequest $serviceRequest);

    abstract public function processResponse($data);
}
