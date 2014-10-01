<?php

class Helper {

    private static function formatTime(\DateTime $dt)
    {
        return $dt->format('Y-m-d H:i:s');
    }

    public static function isVerifyRequested($fromEmail, Database $db)
    {
        // check if we already sent verification request to this sender during last 7 days
        $verificationTime = new \DateTime();
        $verificationTime->sub(new \DateInterval('P7D'));       // 7 days

        $query = "SELECT `id` FROM `messages` WHERE `from_email` = '" . $fromEmail . "'"
            . " AND `status` = 1 "
            . " AND `verify_requested_at` > '" . self::formatTime($verificationTime) . "'";

        $verifyRequested = $db->getArray($query) !== false;
        
        return $verifyRequested;
    }

    /**
     * We need to add another table to store verifications requests.
     * For now we will use messages table
     * 
     * @param type $messageId
     * @param Database $db
     */
    public static function setVerifyRequested($messageId, Database $db)
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
