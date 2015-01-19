<?php

require_once 'boot.php';

$db = new Database();

// modify gmail_access_token column
$query = 'ALTER TABLE users MODIFY `gmail_access_token` text DEFAULT NULL';
$db->execute($query);

// merge gmail token into one field and convert it into JSON representation
$query = 'SELECT * FROM `users`';
$users = $db->getArray($query);
foreach ($users as $user) {
    $token = null;
    if ($user['gmail_access_token'] && $user['gmail_refresh_token']) {
        $token = [
            'access_token' => $user['gmail_access_token'],
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => $user['gmail_refresh_token'],
            'created' => 0
        ];
        $token = json_encode($token);
    }
    $db->updateOne('users', 'gmail_access_token', $token, 'id', $user['id']);
}

// drop column gmail_refresh_token
$query = 'ALTER TABLE users DROP COLUMN gmail_refresh_token';
$db->execute($query);


