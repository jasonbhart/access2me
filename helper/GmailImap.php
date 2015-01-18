<?php

namespace Access2Me\Helper;

use Zend\Mail\Protocol;
use Zend\Mail\Storage;

class StorageFolder
{
    const INBOX = 1;
    const SENT = 2;
    const TRASH = 3;
    const UNVERIFIED = 4;    
    const UNIMPORTANT = 5;    
}

interface StorageFolderInterface
{
    /**
     * @param int $id
     * @return string
     */
    public function getFolderName($id);
}


class GmailImapStorage extends Storage\Imap implements StorageFolderInterface
{
    // Gmail has labels instead of folders
    private $foldersMap = [
        StorageFolder::INBOX => 'INBOX',
        StorageFolder::SENT => '[Gmail]/Sent Mail',
        StorageFolder::TRASH => '[Gmail]/Trash',
        StorageFolder::UNVERIFIED => 'Unverified',
        StorageFolder::UNIMPORTANT => 'Unimportant'
    ];

    public function getFolderName($folderId)
    {
        return $this->foldersMap[$folderId];
    }

    /**
     * Moves message to the Trash
     * 
     * @param int $id number of message
     * @return type
     */
    public function moveToTrashByNumberId($id)
    {
        return $this->copyMessage($id, $this->getFolderName(StorageFolder::FOLDER_TRASH));
    }

    /**
     * Moves messages specified by header `Message-Id` to the Trash
     * @param array $messageIds message id 
     */
    public function moveToTrash(array $messageIds)
    {
        $uids = [];
        foreach ($this->getSize() as $id=>$size) {
            $uids[] = $this->getUniqueId($id);
        }

        foreach ($uids as $uid) {
            $id = $this->getNumberByUniqueId($uid);
            $mid = $this->getMessage($id)->messageId;
            if (in_array($mid, $messageIds)) {
                $this->moveToTrashByNumberId($id);
            }
        }    
    }

    /**
     * Removes messages specified by header `Message-Id`
     * @param array $messageIds message id 
     */
    public function removeMessages(array $messageIds)
    {
        $uids = [];
        foreach ($this->getSize() as $id=>$size) {
            $uids[] = $this->getUniqueId($id);
        }

        foreach ($uids as $uid) {
            $id = $this->getNumberByUniqueId($uid);
            $mid = $this->getMessage($id)->messageId;
            if (in_array($mid, $messageIds)) {
                $this->removeMessage($id);
            }
        }    
    }
}


class GmailImap extends Protocol\Imap
{
    protected function constructAuthString($username, $accessToken) {
        return base64_encode("user=$username\1auth=Bearer $accessToken\1\1");
    }

    public function loginOAuth2($username, $accessToken)
    {
        $authenticateParams = array(
            'XOAUTH2',
            $this->constructAuthString($username, $accessToken)
        );
        $this->sendRequest('AUTHENTICATE', $authenticateParams);

        while (true) {
            $response = "";
            $is_plus = $this->readLine($response, '+', true);
            if ($is_plus) {
                \Logging::getLogger()->addDebug('got an extra server challenge: ' . $response);
                // Send empty client response.
                $this->sendRequest('');
            } else {
                if (preg_match('/^NO /i', $response) || preg_match('/^BAD /i', $response)) {
                    throw new \Exception("got failure response: $response");
                } else if (preg_match("/^OK /i", $response)) {
                    return true;
                } else {
                    // Some untagged response, such as CAPABILITY
                }
            }
        }
    }

    public static function getImap($email, $accessToken)
    {
        $imap = new self('imap.gmail.com', '993', 'ssl');
        $imap->loginOAuth2($email, $accessToken);
        return $imap;
    }

    public static function getImapStorage($user, $db)
    {
        // connect to user's gmail mailbox
        try {
            $mailbox = $user['mailbox'];
            $accessToken = $user['gmail_access_token'];
            $imap = self::getImap($mailbox, $accessToken);
        } catch (\Exception $ex) {

            // check that token is valid
            if (Google::isTokenValid($accessToken)) {
                \Logging::getLogger()->error(
                    sprintf('Google token is valid but we can\'t connect to mailbox (user id: %d)', $user['id']),
                    array('exception' => $ex)
                );
                return null;
            }

            // token is invalid, try to refresh it
            try {
                $accessToken = Google::requestAuthToken($user['gmail_refresh_token']);
            } catch (\Exception $ex) {
                $msg = sprintf('Can\'t refresh google token (user id: %d)', $user['id']);
                \Logging::getLogger()->error($msg);
                return null;
            }

            // now we have fresh token, save it
            $db->updateOne('users', 'gmail_access_token', $accessToken, 'id', $user['id']);

            // try again to connect to gmail
            try {
                $imap = self::getImap($mailbox, $accessToken);
            } catch (\Exception $ex) {
                \Logging::getLogger()->error('Try #2, can\'t connect to user\'s (%d) gmail mailbox', $user['id']);
                return null;
            }
        }

        $storage = new GmailImapStorage($imap);

        return $storage;
    }

}
