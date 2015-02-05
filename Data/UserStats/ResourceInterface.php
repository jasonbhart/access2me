<?php

namespace Access2Me\Data\UserStats;

interface ResourceInterface
{
    public function getType();
    public function get($userId);
}
