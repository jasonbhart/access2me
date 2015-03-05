<?php
/**
 * Created by PhpStorm.
 * User: yup
 * Date: 05.03.15
 * Time: 23:25
 */

namespace Access2Me\Model;


abstract class AbstractRepository
{
    /**
     * Encodes \DateTime object to be saved in storage
     * @param $dt
     * @return null|string
     */
    protected function encodeDateTime($dt)
    {
        return $dt instanceof \DateTimeInterface ? $dt->format('Y-m-d H:i:s') : null;
    }

    /**
     * Converts value to \DateTime object
     * @param $dt
     * @return \DateTime|null
     */
    protected function decodeDateTime($dt)
    {
        try {
            return !empty($dt) ? new \DateTime($dt) : null;
        } catch (\Exception $ex) {
            return null;
        }
    }
}
