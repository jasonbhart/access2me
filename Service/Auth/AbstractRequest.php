<?php

namespace Access2Me\Service\Auth;

abstract class AbstractRequest
{
    /**
     * Scopes that are required for this request
     * @var array
     */
    protected $requiredScopes = [];

    public function getRequiredScopes()
    {
        return $this->requiredScopes;
    }
}
