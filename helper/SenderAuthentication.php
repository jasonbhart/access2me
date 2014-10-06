<?php

namespace Access2Me\Helper;

/**
 * Helper class to work with authentication of sender
 */
class SenderAuthentication
{
    private static function formatTime(\DateTime $dt)
    {
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Get authentication request time for sender
     * 
     * @param string $email sender
     * @param \Database $db
     * @return \DateTime
     */
    private static function getRequestedAt($email, \Database $db)
    {
        // FIXME: sql injection possibility
        $query = "SELECT `requested_at` FROM `auth_requests` WHERE `sender` = '" . $email . "'";
        $result = $db->getArray($query);

        if ($result !== false) {
            $result = $result[0]['requested_at'];
            return \DateTime::createFromFormat('Y-m-d H:i:s', $result);
        }

        return null;
    }

    /**
     * Check if system has already requested authentication from sender
     * 
     * @param string $email sender
     * @param \Database $db
     * @return boolean
     */
    public static function isRequested($email, \Database $db)
    {
        // check if we already sent authentication request to this sender during last 7 days
        $authTimeLimit = new \DateTime();
        $authTimeLimit->sub(new \DateInterval('P7D'));       // 7 days

        // get authentication request time
        $requestedAt = self::getRequestedAt($email, $db);
        
        if ($requestedAt > $authTimeLimit) {
            return true;
        }

        return false;
    }

    /**
     * Creates or updates authentication request time for the sender
     * 
     * @param string $email sender
     * @param Database $db
     */
    public static function setRequested($email, \Database $db)
    {
        $requestedAt = self::getRequestedAt($email, $db);
        $newRequestedAt = self::formatTime(new \DateTime());

        // no record exists ?
        if ($requestedAt === null) {
            $db->insert(
                'auth_requests',
                array('sender', 'requested_at'),
                array($email, $newRequestedAt)
            );
        } else {
            $db->updateOne(
                'auth_requests',
                'requested_at',
                $newRequestedAt,
                'sender',
                $email
            );
        }
    }
}
