<?php

namespace Access2Me\Data\UserStats;

abstract class AbstractResource implements ResourceInterface
{
    protected $isCacheable = true;

    public function isCacheable() {
        return $this->isCacheable;
    }
}
