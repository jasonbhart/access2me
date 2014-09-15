<?php

require_once __DIR__ . "/../boot.php";

$db = new Database;

$query = "SELECT * FROM `messages` WHERE `status` = '0'";
$messages = $db->getArray($query);

if (empty($messages)) {
    die();
}

foreach ($messages AS $message) {
    $query = "SELECT `oauth_key` FROM `senders` WHERE `sender` = '" . $message['from_email'] . "' LIMIT 1";
    $key = $db->getArray($query);

    $query = "SELECT `mailbox`,`name` FROM `users` WHERE `id` = '" . $message['user_id'] . "' LIMIT 1";
    $user = $db->getArray($query);

    if (!$key[0]['oauth_key']) {
        $append  = $user[0]['name'] . ' (' . $user[0]['mailbox'] . '@access2.me) has requested that you verify your identity before communicating with them.';
        $append .= "<br /><br />";
        $append .= 'Please click <a href="' . $localUrl . '/verify.php?message_id=' . $message['id'] . '">here</a> to verify your identity by logging into your LinkedIn or Facebook account.';

        $params = array(
            'host'     => 'smtp.spamarrest.com',
            'port'     => 587,
            'user'     => 'dmerenda',
            'password' => 'drm+jlm'
        );

        $smtp = new SMTP($params);

        $smtp->sendEmail(
            $message['from_email'],
            'Access2.me Verification',
            'catchall@access2.me',
            'Please verify to contact ' . $user[0]['name'],
            $append,
            true,
            null
        );

        $db->updateOne('messages', 'status', '1', 'id', $message['id']);
    } else {
        $db->updateOne('messages', 'status', '2', 'id', $message['id']);
    }
}
