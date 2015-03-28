<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Helper;

$db = new Database;
$auth = Helper\Registry::getAuth();

if (!$auth->isAuthenticated()) {
    Helper\Http::redirect(Helper\Registry::getRouter()->getUrl('login'));
}
