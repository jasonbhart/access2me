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

    public static function getImapStorage(GoogleAuth $auth)
    {
        return new GmailImapStorage(GmailImap::getImap($auth));
    }
}


class GmailImap extends Protocol\Imap
{
    protected function constructAuthString($username, $accessToken) {
        return base64_encode("user=$username\1auth=Bearer $accessToken\1\1");
    }

    public function authenticateWithOAuth2Token($username, $accessToken)
    {
        $authenticateParams = array(
            'XOAUTH2',
            $this->constructAuthString($username, $accessToken)
        );
        $this->sendRequest('AUTHENTICATE', $authenticateParams);
        $this->processOAuth2Response();
    }

    protected function processOAuth2Response()
    {
         while (true) {
            $response = '';
            $is_plus = $this->readLine($response, '+', true);
            if ($is_plus) {
                \Logging::getLogger()->addDebug('processOAuth2Reponse: got an extra server challenge: ' . $response);
                // Send empty client response.
                $this->sendRequest('');
            } else {
                if (preg_match('/^NO /i', $response) || preg_match('/^BAD /i', $response)) {
                    throw new \Exception('got failure response: ' . $response);
                } else if (preg_match('/^OK /i', $response)) {
                    return;
                } else {
                    // Some untagged response, such as CAPABILITY
                }
            }
        }
    }

    /**
     * Connects to user's gmail mailbox using provided token provider
     * Refreshes token if it has expired
     * 
     * @param string $username
     * @param GoogleAuth $auth
     * @throws \Exception
     */
    public function loginWithOAuth2($auth)
    {
        $this->authenticateWithOAuth2Token($auth->username, $auth->token['access_token']);
    }

    public static function getImap(GoogleAuth $auth)
    {
        $imap = new self('imap.gmail.com', '993', 'ssl');
        $imap->loginWithOAuth2($auth);
        return $imap;
    }
}
