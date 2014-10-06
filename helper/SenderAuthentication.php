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

    public static function isRequested($fromEmail, \Database $db)
    {
        // check if we already sent authentication request to this sender during last 7 days
        $authenticationTime = new \DateTime();
        $authenticationTime->sub(new \DateInterval('P7D'));       // 7 days

        $query = "SELECT `id` FROM `messages` WHERE `from_email` = '" . $fromEmail . "'"
            . " AND `status` = 1 "
            . " AND `verify_requested_at` > '" . self::formatTime($authenticationTime) . "'";

        $authenticationRequested = $db->getArray($query) !== false;
        
        return $authenticationRequested;
    }

    /**
     * We need to add another table to store verifications requests.
     * For now we will use messages table
     * 
     * @param type $messageId
     * @param Database $db
     */
    public static function setRequested($messageId, \Database $db)
    {
        $now = new \DateTime();
        $db->updateOne(
            'messages',
            'verify_requested_at',
            self::formatTime($now),
            'id',
            $messageId
        );
    }
}
