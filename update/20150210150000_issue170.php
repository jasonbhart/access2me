<?php

require_once '../boot.php';

$db = new Database();

// update dates for all messages
$mesgRepo = new \Access2Me\Model\MessageRepository($db);
$parser = new \ezcMailParser();

foreach ($mesgRepo->findAll() as $message) {
    $tmp = $message['header']
        . ezcMailTools::lineBreak()
        . ezcMailTools::lineBreak()
        . $message['body']; 
    $mail = $parser->parseMail(new ezcMailVariableSet($tmp));
    
    $dt = \Access2Me\Helper\Email::parseDate($mail[0]->getHeader('Date'));
    $message['created_at'] = $dt;
    $mesgRepo->save($message);
}
