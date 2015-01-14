<?php

namespace Access2Me\Helper;

class DateTime {

    /**
     * Converts text representation of time to DateTime and changes timezone
     * @param string $timestamp
     * @return \DateTime
     */
    public static function fromUTCtoDefault($timestamp)
    {
        $dt = new \DateTime($timestamp, new \DateTimeZone('UTC'));
        // change timezone
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        return $dt;
    }
}
