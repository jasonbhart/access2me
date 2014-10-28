<?php

require_once __DIR__ . "/boot.php";

use Access2Me\Model;

$db = new Database;

if (!$_GET['code']) {
    header('Location: https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=523467224320-5evqo2ovdnqqntulu3531298cp8hfh12.apps.googleusercontent.com&redirect_uri=http%3A%2F%2Fwww.access2.me%2Fgmailoauth.php&access_type=offline&scope=profile');
} else {
    $url = "https://accounts.google.com/o/oauth2/auth?code=" . $_GET['code'] . "&redirect_uri=http%3A%2F%2Fwww.access2.me%2Fgmailoauth.php&client_id=523467224320-5evqo2ovdnqqntulu3531298cp8hfh12.apps.googleusercontent.com&client_secret=8s74XEEucknNhYb6keO0yzBw&grant_type=authorization_code";

    $cURL = curl_init();

    curl_setopt($cURL, CURLOPT_VERBOSE, true);
    curl_setopt($cURL, CURLOPT_URL, $url);
    curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($cURL, CURLOPT_HTTPGET, true);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json'
    ));

    $result = curl_exec($cURL);
    curl_close($cURL);
    $json = json_decode($result, true);

    $accessToken = (string)$json['access_token'];

print_r($json);
}


//CLIENT ID 523467224320-5evqo2ovdnqqntulu3531298cp8hfh12.apps.googleusercontent.com
//EMAIL ADDRESS 523467224320-5evqo2ovdnqqntulu3531298cp8hfh12@developer.gserviceaccount.com
//CLIENT SECRET 8s74XEEucknNhYb6keO0yzBw
//REDIRECT URIS http://www.access2.me/gmailoauth
// JAVASCRIPT ORIGINS http://www.access2.me