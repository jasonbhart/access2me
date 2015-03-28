<?php

namespace Access2Me\Service\Auth;

abstract class AbstractHandler
{
    /**
     * names of request classes that handler can handle
     * @var array
     */
    protected $handledTypes = [];

    /**
     * Checks whether handler can handle the requested object
     *
     * @param $requestObject
     * @return bool
     */
    public function canHandle($requestObject)
    {
        return in_array(get_class($requestObject), $this->handledTypes);
    }

    /**
     * Process service response using requested data
     *
     * @param $serviceRequest data associated with service request
     * @param $serviceResponse data returned by service
     * @return mixed depends on handler
     */
    abstract public function handle($serviceRequest, $serviceResponse);
}
