<?php

namespace Access2Me\Helper;

use Access2Me\Model;

class ProcessingResult
{
    /**
     * @var int one of Model\MessageRepository::STATUS_*
     */
    public $status;

    /**
     * New transformed message
     * @var \ezcMail
     */
    public $message;

    public function __construct($status = Model\MessageRepository::STATUS_NOT_VERIFIED, \ezcMail $message = null)
    {
        $this->status = $status;
        $this->message = $message;
    }
}

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
     * @var \Access2Me\Helper\UserListTokenManager
     */
    private $userListTokenManager;
    
    public function __construct($user, $db, $storage, $userSendersList, $userListTokenManager)
    {
        $this->user = $user;
        $this->db = $db;
        $this->storage = $storage;
        $this->userSendersList = $userSendersList;
        $this->userListTokenManager = $userListTokenManager;
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
            $this->storage->getFolderName($this->getFailuresFolder()),
        	$this->storage->getFolderName(StorageFolder::JUNK)
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

    private function buildWhitelistUrl($email)
    {
        $tokenManager = $this->userListTokenManager;
        
        $baseUrl = 'http://app.access2.me/user_senders.php?';
        $token = $tokenManager->generateToken($this->user['id'], $email);
        $url = $baseUrl . http_build_query([
            'token' => $token,
            'uid' => $this->user['id'],
            'email' => $email,
            'access_type' => Model\UserSenderRepository::ACCESS_ALLOWED
        ]);

        return $url;
    }
    
    private function buildBlacklistUrl($email)
    {
        $tokenManager = $this->userListTokenManager;
        
        $baseUrl = 'http://app.access2.me/user_senders.php?';
        $token = $tokenManager->generateToken($this->user['id'], $email);
        $url = $baseUrl . http_build_query([
            'token' => $token,
            'uid' => $this->user['id'],
            'email' => $email,
            'access_type' => Model\UserSenderRepository::ACCESS_DENIED
        ]);

        return $url;
    }

    /**
     * Appends unverified message to the Unverified folder on Gmail
     * 
     * @param Message entity $message
     * @return ProcessingResult|false
     */
    private function processUnverified($message)
    {
        // append message to Unverified folder if it was not already appended
        if (!$message['appended_to_unverified']) {
            $data['whitelist_url'] = $this->buildWhitelistUrl($message['from_email']);
            $data['blacklist_url'] = $this->buildBlacklistUrl($message['from_email']);
            $mail = Email::buildUnverifiedMessage($this->user, $message, $data);
            return new ProcessingResult(Model\MessageRepository::STATUS_NOT_VERIFIED, $mail);
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
     * Prepares profile data to be used in email header
     * 
     * @param ProfileCombiner $profile
     */
    public function getProfileViewData(ProfileCombiner $profile)
    {
        // data for template
        $data = [
            'picture_url' => $profile->getFirst('pictureUrl'),
            'profile_urls' => $profile->profileUrl,
            'email' => $profile->getFirst('email'),
            'full_name' => $profile->getFirst('fullName'),
            'headline' => $profile->getFirst('headline'),
            'location' => $profile->getFirst('location'),
            'summary'  => $profile->getFirst('summary')
        ];

        $data['linkedin'] = $profile->linkedin;
        $data['angel_list'] = $profile->angelList;
        $data['crunch_base'] = $profile->crunchBase;
        $data['github'] = $profile->gitHub;
        $data['klout'] = $profile->klout;
        $data['aboutme'] = $profile->aboutme;

        // use only if realness of profile is above 80%
        if (isset($profile->fullContact) && isset($profile->fullContact->likelihood) && $profile->fullContact->likelihood > 0.8) {
            $data['full_contact'] = $profile->fullContact;
        }

        // remove full_contact social profile that user authenticated
        if (!empty($data['full_contact']->socialProfiles) && !empty($data['profile_urls'])) {
            foreach ($data['full_contact']->socialProfiles as $index => $socialProfile) {
                if (in_array($socialProfile['url'], $data['profile_urls'])) {
                    unset($data['full_contact']->socialProfiles[$index]);
                }
            }
        }

        return $data;
    }

    private function processUserLists($message)
    {
        $sender = $message['from_email'];

        // does sender's address matches some list ?
        $result = $this->userSendersList->search($sender);
        if ($result === false) {
            return false;
        }

        $profile = $this->getSenderProfile($message['from_email']);
        $data = [];
        if ($profile) {
        	$data['profile'] = $this->getProfileViewData($profile);
        }

        // whitelisted ?
        if ($result['access'] == Model\UserSenderRepository::ACCESS_ALLOWED) {
        	$data['reason'] = 'This sender was whitelisted.';
        	$status = Model\MessageRepository::STATUS_SENDER_WHITELISTED;
        } else {
        	$data['reason'] = 'This sender was blacklisted.';
        	$status = Model\MessageRepository::STATUS_SENDER_BLACKLISTED;
        }

        $mail = Email::buildUserListProcessedMessage($this->user, $message, $data);
        return new ProcessingResult($status, $mail);
    }

    /**
     * In case sender's profile passes filtering rules
     * `Verified by` header is added to the mail body and it is appended
     * to the user's Gmail INBOX.
     *
     * @param Message entity $message
     * @return ProcessingResult|false
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

        $result = new ProcessingResult();
        $data = ['profile' => $profile];
        // todo: move filter out of class
        $filter = new \Filter($this->user['id'], $profile, $this->db);
        $filter->processFilters();

        if ($filter->status === true) {
            $result->status = Model\MessageRepository::STATUS_FILTER_PASSED;
        } else {
            $data['failed_filters'] = $filter->getFailedFilters();
            $result->status = Model\MessageRepository::STATUS_FILTER_FAILED;
        }
        
        $data['whitelist_url']  = $this->buildWhitelistUrl($message['from_email']);
        $data['blacklist_url']  = $this->buildBlacklistUrl($message['from_email']);
        $data['profile'] = $this->getProfileViewData($profile);
        
        // build email
        $result->message = Email::buildVerifiedMessage(
            $this->user,
            $message,
            $data
        );

        return $result;
    }

    /**
     * @param array $message message entity
     * @return ProcessingResult|false
     */
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

    /**
     * 
     * @param string $mail
     * @param int $status one of Model\MessageRepository::STATUS_*
     * @throws \InvalidArgumentException
     */
    protected function putMessage($mail, $status)
    {
        $dstFolders = [
            Model\MessageRepository::STATUS_NOT_VERIFIED => StorageFolder::UNVERIFIED,
            Model\MessageRepository::STATUS_SENDER_WHITELISTED => StorageFolder::INBOX,
        	Model\MessageRepository::STATUS_SENDER_BLACKLISTED => StorageFolder::JUNK,
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
                if (in_array($result->status, $processedStatuses, true)) {
                    // if we have transformed message
                    if ($result->message) {
                        $this->putMessage($result->message->generate(), $result->status);
                    }

                    // remove from unverified ?
                    $remove = $message['appended_to_unverified'] && $this->canRemoveUnverified($result->status);

                    // if message was in unverified folder before we need to remove it from there
                    if ($remove) {
                        $removeFromUnverified[] = $message['appended_to_unverified'];
                    }

                    // flag message as processed
                    $this->db->updateOne('messages', 'status', $result->status, 'id', $message['id']);
                    $this->db->updateOne('messages', 'appended_to_unverified', null, 'id', $message['id']);
                } else if ($result->status === Model\MessageRepository::STATUS_NOT_VERIFIED) {
                    // put message to `unverified` box
                    if (!$message['appended_to_unverified']) {
                        $this->putMessage($result->message->generate(), $result->status);
                        $this->db->updateOne(
                            'messages',
                            'appended_to_unverified',
                            $result->message->getHeader('Message-Id'),
                            'id',
                            $message['id']
                        );
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
