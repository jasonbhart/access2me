<?php

require_once __DIR__ . "/../boot.php";
$db = new Database;

if (!isset($_COOKIE['a2muser'])) {
    header('Location: login.php');
} else {
    if (!isset($_COOKIE['a2mauth'])) {
        header('Location: login.php');
    } else {
        $sql = "SELECT `password` FROM `users` WHERE `username` = '" . $_COOKIE['a2muser'] . "' LIMIT 1;";
        $password = $db->getArray($sql);

        if ($password[0]['password'] == $_COOKIE['a2mauth']) {
            // authorized
        } else {
            header('Location: login.php');
        }
    }
}
