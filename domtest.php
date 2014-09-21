<?php

require_once __DIR__ . "/boot.php";

$db = new Database;

$params = array(
'host'     => 'mail.access2.me',
'port'     => 587,
'user'     => 'noreply@access2.me',
'password' => 'access123sakjsd'
);

$smtp = new SMTP($params);

$smtp->sendEmail(
    'dom@leadwrench.com',
    'Dom',
    'dom@edgeprod.com',
    'Subject',
    'Body',
    false,
    null
);

echo $smtp->getLastStatus();
echo "<br />";
echo $smtp->getLastError();
echo "<br />";
print_r($smtp->getErrors());
