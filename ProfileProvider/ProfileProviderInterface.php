<?php

namespace Access2Me\ProfileProvider;

interface ProfileProviderInterface
{
    /**
     * @param \Access2Me\Model\Sender $sender
     * @return array
     */
    public function fetchProfile($sender);
}
