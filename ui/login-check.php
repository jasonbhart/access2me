<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Helper\Auth;

$db = new Database;
$auth = new Auth($db);

if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}
