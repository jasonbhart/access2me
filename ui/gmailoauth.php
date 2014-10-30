<?php

require_once __DIR__ . "/../boot.php";

$db = new Database;

// Check for a Gmail OAuth Token for the current user

$sql = "SELECT `gmail_access_token` FROM `users` WHERE `username` = '" . $_COOKIE['a2muser'] . "' LIMIT 1;";
$result = $db->getArray($sql);

if (!empty($result[0]['gmail_access_token'])) {
    // Check if our OAuth Token Works
    $url = 'https://www.googleapis.com/gmail/v1/users/me/profile';

    $cURL = curl_init();

    curl_setopt($cURL, CURLOPT_VERBOSE, true);
    curl_setopt($cURL, CURLOPT_URL, $url);
    curl_setopt($cURL, CURLOPT_HTTPGET, true);
    curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $result[0]['gmail_access_token']
    ));

    $result = curl_exec($cURL);
    curl_close($cURL);

    $json = json_decode($result, true);

    $accountName   = (string)$json['emailAddress'];
    $totalMessages = (string)$json['messagesTotal'];

    // If not, refresh the token if we have a refresh token
} else {
        // Get a new Token
        if (!$_GET['code']) {
            header('Location: https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=523467224320-5evqo2ovdnqqntulu3531298cp8hfh12.apps.googleusercontent.com&redirect_uri=http%3A%2F%2Fapp.access2.me%2Fui%2Fgmail-config.php&access_type=offline&approval_prompt=force&scope=https%3A%2F%2Fmail.google.com%2F');
        } else {
        $url = 'https://accounts.google.com/o/oauth2/token';
        $fields = array(
            'code' => $_GET['code'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://app.access2.me/ui/gmail-config.php',
            'client_id' => '523467224320-5evqo2ovdnqqntulu3531298cp8hfh12.apps.googleusercontent.com',
            'client_secret' => '8s74XEEucknNhYb6keO0yzBw'
        );
        $query = http_build_query($fields, '', '&');

        $cURL = curl_init();

        curl_setopt($cURL, CURLOPT_VERBOSE, true);
        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURL, CURLOPT_POST, 1);
        curl_setopt($cURL, CURLOPT_POSTFIELDS, $query);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($cURL);
        curl_close($cURL);

        $json = json_decode($result, true);

        $accessToken  = (string)$json['access_token'];
        $refreshToken = (string)$json['refresh_token'];

        if (!empty($accessToken)) {
            // Store the access token in the DB
            $db->updateOne('users', 'gmail_access_token', $accessToken, 'username', $_COOKIE['a2muser']);
        }

        if (!empty($refreshToken)) {
            // Store the refresh code in the DB
            $db->updateOne('users', 'gmail_refresh_token', $refreshToken, 'username', $_COOKIE['a2muser']);
        }

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