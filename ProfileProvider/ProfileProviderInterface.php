<?php

namespace Access2Me\ProfileProvider;

interface ProfileProviderInterface
{
    const DEPENDENCY_OPTIONAL = 1;
    const DEPENDENCY_REQUIRED = 2;
    
    /**
     * @param \Access2Me\Model\Sender $sender
     * @param array $dependencies profiles that this provider depends on
     * @return array|object
     */
    public function fetchProfile(\Access2Me\Model\Sender $sender, array $dependencies = []);
}
