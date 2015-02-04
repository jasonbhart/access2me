<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Helper;
use Access2Me\Model;

$db = new Database;

// Check for a Gmail OAuth Token for the current user
$auth = new Helper\Auth($db);

if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getLoggedUser();
$userRepo = new Model\UserRepository($db);

// get auth token provider
$authProvider = new Helper\GoogleAuthProvider(
    $appConfig['services']['gmail'],
    $userRepo
);

// fetch user's profile if we have access token
if ($user['gmail_access_token'] != null) {
    $googleAuth = $authProvider->getAuth($user['username']);
    $profile = \Access2Me\Service\Gmail::getProfile($googleAuth->client);

    $accountName   = $profile['emailAddress'];
    $totalMessages = $profile['messagesTotal'];
} else {
    $client = $authProvider->getClient();
    // interactively request access token
    $client->setRedirectUri('http://app.access2.me/ui/gmail-config.php');

    // Request a new Token
    if (!isset($_GET['code'])) {
        $client->setAccessType('offline');          // we also need refresh_token
        $client->setApprovalPrompt('force');
        $client->addScope('https://mail.google.com/');
        header('Location: ' . $client->createAuthUrl());
        exit;
    } else {
        $client->authenticate($_GET['code']);

        // Store token in the DB
        $user['gmail_access_token'] = $client->getAccessToken();
        $userRepo->save($user);

        header('Location: gmail-config.php');
    }
}

//REFRESH TOKEN

//POST /o/oauth2/token HTTP/1.1
//Host: accounts.google.com
//Content-Type: application/x-www-form-urlencoded
//
//client_id=812741506391-h38jh0j4fv0ce1krdkiq0hfvt6n5amrf.apps.googleusercontent.com&
//client_secret={clientSecret}&
//refresh_token=1/551G1yXUqgkDGnkfFk6ZbjMLMDIMxo3JFc8lY8CAR-Q&
//grant_type=refresh_token
