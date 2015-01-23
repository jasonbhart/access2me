<?php

namespace Access2Me\ProfileProvider;

interface ProfileProviderInterface
{
    /**
     * @param \Access2Me\Model\Sender $sender
     * @return array|object
     */
    public function fetchProfile(\Access2Me\Model\Sender $sender);
}
