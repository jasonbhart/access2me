<?php

require_once __DIR__ . '/../boot.php';

use Access2Me\Helper;
use Access2Me\Model;


$db = new Database();
$auth = Helper\Registry::getAuth();

if (!$auth->isAuthenticated()) {
    Helper\Http::generate403();
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

// save
if ($action == 'save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $sender = isset($_POST['sender']) ? $_POST['sender'] : null;
    $type = isset($_POST['type']) ? (int)$_POST['type'] : 0;
    $access = isset($_GET['access']) ? (int)$_GET['access'] : 0;

    // validate data
    if (!in_array($type, [Model\UserSenderRepository::TYPE_DOMAIN, Model\UserSenderRepository::TYPE_EMAIL])
        || !in_array($access, [Model\UserSenderRepository::ACCESS_ALLOWED, Model\UserSenderRepository::ACCESS_DENIED])
    ) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid type or access type'
        ]);
        exit;
    }

    // validate sender's address (email/domain)
    if (!Helper\UserListProvider::isAddressValid($sender, $type)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid sender'
        ]);
        exit;
    }

    // store only domain part if type is domain
    if ($type == Model\UserSenderRepository::TYPE_DOMAIN && Helper\Validator::isValidEmail($sender)) {
        $sender = Helper\Email::splitEmail($sender)['domain'];
    }

    $user = $auth->getLoggedUser();
    $repo = new Model\UserSenderRepository($db);
    $entry = null;

    // load the requested entry
    if ($id > 0) {
        $entry = $repo->get($id);
        if ($entry === null) {
            Helper\Http::generate404();
        }

        // check if user is the owner
        if ($entry['user_id'] != $user['id']) {
            Helper\Http::generate403();
        }
    }

    // search for the existing user/sender pair
    $existing = $repo->getByUserAndSender($user['id'], $sender);
    if ($existing != null) {
        if ($entry != null) {
            // is user trying to add sender that is already exists in another record ?
            if ($existing['id'] != $entry['id']) {
                Helper\Http::jsonResponse([
                    'status' => 'error',
                    'message' => 'Record for such sender already exists'
                ]);
            }
        } else {
            // entry is null
            $entry = $existing;
        }
    }

    // no record exists ?
    if ($entry == null) {
        $entry = [
            'user_id' => $user['id']
        ];
    }

    $entry['sender'] = $sender;
    $entry['type'] = $type;
    $entry['access'] = $access;
    $id = $repo->save($entry);
    
    echo json_encode([
        'status' => 'success',
        'sender' => $sender,
        'id' => $id 
    ]);
    exit;
} else if ($action == 'delete') {           // delete
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $user = $auth->getLoggedUser();
    $repo = new Model\UserSenderRepository($db);
    $entry = $repo->get($id);
    if ($entry === null) {
        Helper\Http::generate404();
    }
    
    // check if user is the owner
    if ($entry['user_id'] != $user['id']) {
        Helper\Http::generate403();
    }

    if ($repo->delete($id) > 0) {
        echo json_encode([
            'status' => 'success',
            'id' => $id
        ]);
    } else {
        echo json_encode(['status' => 'success']);
    }

    exit;
} else if ($action == 'check-sender') {
    $sender = isset($_GET['sender']) ? $_GET['sender'] : null;
    $type = isset($_GET['type']) ? $_GET['type'] : 0;
    
    // validate sender's address (email/domain)
    if (!validateSenderAddress($sender, $type)) {
        echo 'false';
    } else {
        echo 'true';
    }
    exit;
}

Helper\Http::generate404();
