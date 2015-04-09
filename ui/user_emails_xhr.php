<?php

require_once __DIR__ . '/../boot.php';

use Access2Me\Helper;
use Access2Me\Model;

$db = new Database();
$auth = Helper\Registry::getAuth();
$userEmailRepo = new Model\UserEmailRepository($db);

if (!$auth->isAuthenticated()) {
    Helper\Http::generate403();
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

// save
if ($action == 'save') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $email = isset($_POST['email']) ? $_POST['email'] : null;

    if (!Helper\Validator::isValidEmail($email)) {
        Helper\Http::jsonResponse(['status' => 'error', 'message' => 'Invalid email address']);
    }

    $user = $auth->getLoggedUser();

    // add email
    if ($id > 0) {      // update
        
        $userEmail = $userEmailRepo->getById($id);
        if ($userEmail === null) {
            Helper\Http::generate404();
        }

        // check if user is the owner
        if ($userEmail->getUserId() != $user['id']) {
            Helper\Http::generate403();
        }
    } else {            // insert
        $userEmail = $userEmailRepo->getByUserIdAndEmail($user['id'], $email);
        if ($userEmail !== null) {
            Helper\Http::jsonResponse([
                'status' => 'error',
                'message' => 'Such email already exists'
            ]);
        }

        $userEmail = new Model\UserEmail();
        $userEmail->setUserId($user['id']);
    }

    $userEmail->setEmail($email);
    $id = $userEmailRepo->save($userEmail);

    Helper\Http::jsonResponse(['status' => 'success', 'id' => $id]);

} else if ($action == 'delete') {           // delete
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $userEmail = $userEmailRepo->getById($id);
    if ($userEmail === null) {
        Helper\Http::generate404();
    }

    // check if user is the owner
    $user = $auth->getLoggedUser();
    if ($userEmail->getUserId() != $user['id']) {
        Helper\Http::generate403();
    }

    $userEmailRepo->delete($userEmail->getId());
    
    Helper\Http::jsonResponse(['status' => 'success', 'id' => $id]);
}

Helper\Http::generate404();
