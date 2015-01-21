<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Model;
use Access2Me\Helper;

/**
 * Import recipients from Gmail's Sent folder into the user's whitelist
 */

class RecipientsImporter
{
    private $user;
    private $gmailStorage;
    private $userSenderRepo;

    public function __construct($user, $gmailStorage, $userSenderRepo)
    {
        $this->user = $user;
        $this->gmailStorage = $gmailStorage;
        $this->userSenderRepo = $userSenderRepo;
    }

    public function import()
    {
        // open sent folder
        $this->gmailStorage->selectFolder($this->gmailStorage->getFolderName(Helper\StorageFolder::SENT));

        // loop through messages
        foreach ($this->gmailStorage as $message) {
            $headers = $message->getHeaders();
            
            if (!$headers->has('To')) {
                continue;
            }

            // add recipient addr to whitelist
            $to = $headers->get('To');
            foreach ($to->getAddressList() as $address) {
                $email = $address->getEmail();

                $entry = $this->userSenderRepo->getByUserAndSender($this->user['id'], $email);
                // create whitelisted entry if it doesn't exist
                if ($entry == null) {
                    $entry = [
                        'user_id' => $this->user['id'],
                        'sender' => $email,
                        'type' => Model\UserSenderRepository::TYPE_EMAIL,
                        'access' => Model\UserSenderRepository::ACCESS_ALLOWED
                    ];
                    $this->userSenderRepo->save($entry);
                }
            }
        }
    }
}

$db = new Database;
$userRepo = new Model\UserRepository($db);
$authProvider = new Helper\GoogleAuthProvider($appConfig['services']['gmail'], $userRepo);

foreach ($userRepo->findAll() as $user) {
    try {

        if ($user['recipients_imported'] == true) {
            Logging::getLogger()->debug('Recipients already imported for user: ' . $user['id']);
            continue;
        }
        
        $googleAuth = $authProvider->getAuth($user['username']);
        $storage = Helper\GmailImapStorage::getImapStorage($googleAuth);
        
        if ($storage == null) {
            Logging::getLogger()->info('Can\'t get storage handle for user: ' . $user['id']);
            continue;
        }

        $userSenderRepo = new Model\UserSenderRepository($db);

        // import
        $importer = new RecipientsImporter($user, $storage, $userSenderRepo);
        $importer->import();

        // flag user as processed
        $user['recipients_imported'] = 1;
        $userRepo->save($user);
        
        $storage->close();
    } catch (Exception $ex) {
        Logging::getLogger()->error('Whitelisting senders of user: ' . $user['id'], ['exception' => $ex]);
    }
}