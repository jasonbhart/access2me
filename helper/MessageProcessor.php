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

    /**
     * @var \Access2Me\Helper\AuthTokenManager
     */
    private $authTokenManager;
    
    public function __construct($user, $db, $storage, $userSendersList, $authTokenManager)
    {
        $this->user = $user;
        $this->db = $db;
        $this->storage = $storage;
        $this->userSendersList = $userSendersList;
        $this->authTokenManager = $authTokenManager;
    }

    // get folder from user settings
    // where messages that failed filtering rules will be stored
    protected function getFailuresFolder()
    {
        // todo:
        // if ($user['folder_for_filtering failed'] == ...)
        return StorageFolder::UNIMPORTANT;
    }

    /**
     * Prepares required folders
     */
    protected function prepareFolders()
    {
        // find if we already have `unverified` label
        $rootFolder = null;

        $folders = [
            $this->storage->getFolderName(StorageFolder::UNVERIFIED),
            $this->storage->getFolderName($this->getFailuresFolder())
        ];

        $folders = array_unique($folders);

        foreach ($folders as $name) {
            $found = false;
            foreach ($this->storage->getFolders($rootFolder) as $folder) {
                if ($folder->getLocalName() == $name) {
                    $found = true;
                    break;
                }
            }

            // create folder
            if (!$found) {
                $this->storage->createFolder($name, $rootFolder);
            }
        }
    }

    private function buildWhitelistUrls($email)
    {
        $splitted = Email::splitEmail($email);
        if (!$splitted) {
            throw new \InvalidArgumentException('email');
        }

        $tokenManager = $this->authTokenManager;
        
        $url = 'http://app.access2.me/user_senders.php?';
        $token = $tokenManager->generateToken($this->user['id'], [Model\Roles::USER_LIST_MANAGER]);
        $result['email'] = $url . http_build_query([
            'token' => $token['token'],
            'email' => $email
        ]);

        $token = $tokenManager->generateToken($this->user['id'], [Model\Roles::USER_LIST_MANAGER]);
        $result['domain'] = $url . http_build_query([
            'token' => $token['token'],
            'domain' => $splitted['domain']
        ]);

        return $result;
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
            $data['whitelist_urls'] = $this->buildWhitelistUrls($message['from_email']);
            $mail = Email::buildUnverifiedMessage($this->user, $message, $data);
            return [
                'message' => $mail->generate(),
                'status' => Model\MessageRepository::STATUS_NOT_VERIFIED
            ];
        }

        return false;
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
        
        if (!$services) {
            return false;
        }

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
     * @todo Do we need to add info header to such messages ? 
     */
    private function processUserLists($message)
    {
        $sender = $message['from_email'];

        // does sender's address matches some list ?
        $result = $this->userSendersList->search($sender);
        if ($result === false) {
            return false;
        }

        // whitelisted ?
        if ($result['access'] == Model\UserSenderRepository::ACCESS_ALLOWED) {
            $mail = Email::buildMessage($this->user, $message);
            $result = [
                'message' => $mail->generate(),
                'status' => Model\MessageRepository::STATUS_SENDER_WHITELISTED
            ];
            return $result;
        }

        // blacklisted
        $msg = sprintf(
            'Do not sending message %d because sender %s is blacklisted',
            $message['id'], $sender
        );
        \Logging::getLogger()->debug($msg);

        return ['status' => Model\MessageRepository::STATUS_SENDER_BLACKLISTED];
    }

    /**
     * In case sender's profile passes filtering rules
     * `Verified by` header is added to the mail body and it is appended
     * to the user's Gmail INBOX.
     *
     * @param Message entity $message
     * @return boolean
     */
    private function processFilters($message)
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

        $result = [];
        $mailOptions = ['profile' => $profile];
        // todo: move filter out of class
        $filter = new \Filter($this->user['id'], $profile, $this->db);
        $filter->processFilters();

        if ($filter->status === true) {
            $result['status'] = Model\MessageRepository::STATUS_FILTER_PASSED;
        } else {
            $mailOptions['failed_filters'] = $filter->getFailedFilters();
            $mailOptions['whitelist_urls'] = $this->buildWhitelistUrls($message['from_email']);
            $result['status'] = Model\MessageRepository::STATUS_FILTER_FAILED;
        }

        // build email
        $mail = Email::buildVerifiedMessage(
            $this->user,
            $message,
            $mailOptions
        );

        $result['message'] = $mail->generate();
        
        return $result;
    }

    public function processMessage($message)
    {
        // check white/blacklists
        $status = $this->processUserLists($message);
        
        if ($status === false) {
            // check filtering rules
            $status = $this->processFilters($message);
        }

        // if we can't verify this message put it into Unverified folder
        if ($status === false) {
            $status = $this->processUnverified($message);
        }
        
        return $status;
    }

    // Message that failed to pass through filters can be stored in Unverified folder
    // This determines if message can be removed
    protected function canRemoveUnverified($status)
    {
        return $status !== Model\MessageRepository::STATUS_FILTER_FAILED
            || $this->getFailuresFolder() !== StorageFolder::UNVERIFIED;
    }

    protected function putMessage($mail, $status)
    {
        $dstFolders = [
            Model\MessageRepository::STATUS_NOT_VERIFIED => StorageFolder::UNVERIFIED,
            Model\MessageRepository::STATUS_SENDER_WHITELISTED => StorageFolder::INBOX,
            Model\MessageRepository::STATUS_FILTER_PASSED => StorageFolder::INBOX,
            Model\MessageRepository::STATUS_FILTER_FAILED => $this->getFailuresFolder()
        ];

        if (!array_key_exists($status, $dstFolders)) {
            throw new \InvalidArgumentException('status');
        }

        $this->storage->appendMessage(
            $mail,
            $this->storage->getFolderName($dstFolders[$status]),
            array(\Zend\Mail\Storage::FLAG_RECENT)
        );
    }

    /**
     * Passes messages to processing methods depenging on message status.
     * Verified messages are removed from Gmail's Unverified folder.
     * 
     * @param array of Message entities $messages
     */
    public function process($messages)
    {
        $this->prepareFolders();

        $notprocessedStatuses = [
            Model\MessageRepository::STATUS_NOT_VERIFIED,
            Model\MessageRepository::STATUS_VERIFY_REQUESTED,
            Model\MessageRepository::STATUS_VERIFIED
        ];
        
        $processedStatuses = [
            Model\MessageRepository::STATUS_SENDER_WHITELISTED,
            Model\MessageRepository::STATUS_SENDER_BLACKLISTED,
            Model\MessageRepository::STATUS_FILTER_PASSED,
            Model\MessageRepository::STATUS_FILTER_FAILED
        ];

        $removeFromUnverified = [];
        foreach ($messages as $message) {
            try {
                $result = false;

                // if message is not processed...
                if (in_array((int)$message['status'], $notprocessedStatuses, true)) {
                    $result = $this->processMessage($message);
                }

                if ($result === false) {
                    continue;
                }

                // processing result
                if (in_array($result['status'], $processedStatuses, true)) {
                    // if we have transformed message
                    if (isset($result['message'])) {
                        $this->putMessage($result['message'], $result['status']);
                    }

                    // remove from unverified ?
                    $remove = $message['appended_to_unverified'] && $this->canRemoveUnverified($result['status']);

                    // flag message as processed
                    $this->db->updateOne('messages', 'status', $result['status'], 'id', $message['id']);
                    $this->db->updateOne('messages', 'appended_to_unverified', 0, 'id', $message['id']);

                    // if message was in unverified folder before we need to remove it from there
                    if ($remove) {
                        $removeFromUnverified[] = $message['message_id'];
                    }
                } else if ($result['status'] === Model\MessageRepository::STATUS_NOT_VERIFIED) {
                    // put message to `unverified` box
                    if (!$message['appended_to_unverified']) {
                        $this->putMessage($result['message'], $result['status']);
                        $this->db->updateOne('messages', 'appended_to_unverified', 1, 'id', $message['id']);
                    }
                }

            } catch (\Exception $ex) {
                \Logging::getLogger()->error(
                    'Can\'t process message: ' . $message['id'], 
                    array('exception' => $ex)
                );
            }
        }

        // remove processed messages from `unverified` folder
        if ($removeFromUnverified) {
            $this->storage->selectFolder($this->storage->getFolderName(StorageFolder::UNVERIFIED));
            $this->storage->moveToTrash($removeFromUnverified);
            $this->storage->selectFolder($this->storage->getFolderName(StorageFolder::TRASH));
            $this->storage->removeMessages($removeFromUnverified);
        }
    }
}
