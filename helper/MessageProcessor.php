<?php

namespace Access2Me\Helper;

use Access2Me\Model;

/**
 * Process messages
 */
class MessageProcessor
{
    private $db;
    private $user;

    private $storage;

    /**
     * @var \Access2Me\Helper\UserListProvider
     */
    private $userSendersList;
    
    private $unverifiedFolderName = 'Unverified';

    public function __construct($user, $db, $storage, $userSendersList) {
        $this->user = $user;
        $this->db = $db;
        $this->storage = $storage;
        $this->userSendersList = $userSendersList;
    }

    /**
     * Creates folder for unverified messages
     * Gmail doesn't use folders. It has labels instead
     */
    private function createUnverifiedFolder()
    {
        // find if we already have `unverified` label
        $found = false;
        $rootFolder = null;
        foreach ($this->storage->getFolders($rootFolder) as $folder) {
            if ($folder->getLocalName() == $this->unverifiedFolderName) {
                $found = true;
                break;
            }
        }

        // create unverified folder
        if (!$found) {
            $this->storage->createFolder($this->unverifiedFolderName, $rootFolder);
        }
    }

    /**
     * Appends unverified message to the Unverified folder on Gmail
     * 
     * @param Message entity $message
     * @return boolean
     */
    private function processUnverified($message)
    {
        // append message to Unverified folder if it was not already appended
        if (!$message['appended_to_unverified']) {
            $mail = $message['header'] . "\r\n\r\n" . $message['body'];
            // put message to `unverified` box
            $this->storage->appendMessage($mail, $this->unverifiedFolderName, array(\Zend\Mail\Storage::FLAG_RECENT));
            $this->db->updateOne('messages', 'appended_to_unverified', 1, 'id', $message['id']);
        }

        return true;
    }

    /**
     * 
     * @param string $sender email
     * @return ProfileCombiner
     */
    private function getSenderProfile($sender)
    {
        // get sender's profile
        // get all service sender is authenticated with
        $repo = new Model\SenderRepository($this->db);
        $services = $repo->getByEmail($sender);

        // get all sender's profiles
        $defaultProfileProvider = Registry::getProfileProvider();
        $profiles = $defaultProfileProvider->getProfiles($services);

        if ($profiles == null) {
            $errMsg = 'Can\'t retrieve profile of ' . $sender;
            \Logging::getLogger()->error($errMsg);
            return null;
        }

        $profComb = new ProfileCombiner($profiles);
        
        return $profComb;
    }

    /**
     * In case sender's profile passes filtering rules
     * `Verified by` header is added to the mail body and it is appended
     * to the user's Gmail INBOX.
     *
     * @param Message entity $message
     * @return boolean
     */
    private function processVerified($message)
    {
        // todo:
        // get profile from Profileprovider
        // pass it to Filter
        // in case profile passes fltering rules pass it to buildVerifiedMessage

        // pass senders profile through the filters
        $profile = $this->getSenderProfile($message['from_email']);
        if (!$profile) {
            return false;
        }

        $processed = false;
        // todo: move filter out of class
        $filter = new \Filter($this->user['id'], $profile, $this->db);
        $filter->processFilters();

        if ($filter->status === true) {
            try {
                // attach our `verified by` header and append message to gmail
                $mail = Email::buildVerifiedMessage($this->user, $profile, $message);

                // append message to mailbox
                $newMessage = $mail->generate();
                $this->storage->appendMessage($newMessage, null, array(\Zend\Mail\Storage::FLAG_RECENT));
                $this->db->updateOne('messages', 'status', Model\MessageRepository::STATUS_FILTER_PASSED, 'id', $message['id']);
                $processed = true;

            } catch (Exception $ex) {
                \Logging::getLogger()->error(
                    'Can\'t append message to mailbox: ' . $message['id'], 
                    array('exception' => $ex)
                );
            }

        } else {
            $this->db->updateOne('messages', 'status', Model\MessageRepository::STATUS_FILTER_FAILED, 'id', $message['id']);
            $processed = true;
        }

        return $processed;
    }

    /**
     * Passes messages to processing methods depenging on message status.
     * Verified messages are removed from Gmail's Unverified folder.
     * 
     * @param array of Message entities $messages
     */
    public function process($messages)
    {
        // check sender is in white/black list
        $notProcessed = [];
        foreach ($messages as $message) {
            $sender = $message['from_email'];

            // doesn sender's address matches some list ?
            $result = $this->userSendersList->search($sender);
            if ($result === false) {
                $notProcessed[] = $message;
                continue;
            }

            // whitelisted ?
            if ($result['access'] == Model\UserSenderRepository::ACCESS_ALLOWED) {
                $mail = Email::buildMessage($this->user, $message);
                $newMessage = $mail->generate();
                $this->storage->appendMessage($newMessage, null, array(\Zend\Mail\Storage::FLAG_RECENT));
                $this->db->updateOne('messages', 'status', Model\MessageRepository::STATUS_SENDER_WHITELISTED, 'id', $message['id']);
            } else {
                // blacklisted
                $this->db->updateOne('messages', 'status', Model\MessageRepository::STATUS_SENDER_BLACKLISTED, 'id', $message['id']);
                $msg = sprintf(
                    'Do not sending message %d because sender %s is blacklisted',
                    $message['id'], $sender
                );
                \Logging::getLogger()->debug($message);
            }
        }

        $this->createUnverifiedFolder();

        $verified = [];
        
        foreach ($notProcessed as $message) {
            if ($message['status'] == Model\MessageRepository::STATUS_NOT_VERIFIED
                || $message['status'] == Model\MessageRepository::STATUS_VERIFY_REQUESTED
            ) {
                $this->processUnverified($message);
            } else if ($message['status'] == Model\MessageRepository::STATUS_VERIFIED) {
                if ($this->processVerified($message)) {
                    $verified[] = $message['message_id'];
                }
            }
        }

        // remove verified messages from `unverified` folder
        if ($verified) {
            $this->storage->selectFolder($this->unverifiedFolderName);
            $this->storage->moveToTrash($verified);
            $this->storage->selectFolder(GmailImapStorage::FOLDER_TRASH);
            $this->storage->removeMessages($verified);
        }
    }
}
