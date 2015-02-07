<?php

namespace Access2Me\Data\UserStats;

interface ResourceInterface
{
    public function getType();

    /**
     * @param array $user user entity
     * @return mixed
     */
    public function get($user);
}
