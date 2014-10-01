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

    // send verification request to sender
    if (!$key[0]['oauth_key']) {

        // did we already requested verification ?
        $verifyRequested = Helper::isVerifyRequested($message['from_email'], $db);

        if (!$verifyRequested) {
            $append  = $user[0]['name'] . ' (' . $user[0]['mailbox'] . '@access2.me) has requested that you verify your identity before communicating with them.';
            $append .= "<br /><br />";
            $append .= 'Please click <a href="' . $localUrl . '/verify.php?message_id=' . $message['id'] . '">here</a> to verify your identity by logging into your LinkedIn or Facebook account.';

            $mail = new PHPMailer;

            $mail->isSMTP();
            $mail->Host = 'mail.access2.me';
            $mail->SMTPAuth = true;
            $mail->Username = 'noreply@access2.me';
            $mail->Password = 'access123';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->From = 'noreply@access2.me';
            $mail->FromName = 'Access2.ME';
            $mail->addAddress($message['from_email']);
            $mail->XMailer = ' ';
            $mail->Hostname = 'access2.me';

            $mail->isHTML(true);

            $mail->Subject = 'Access2.ME Verification';
            $mail->Body    = $append;

            if(!$mail->send()) {
                echo 'Mailer Error: ' . $mail->ErrorInfo;
            } else {
                Helper::setVerifyRequested($message['id'], $db);
                $verifyRequested = true;
            }
        }

        if ($verifyRequested) {
            $db->updateOne('messages', 'status', '1', 'id', $message['id']);
        }
    } else {
        $db->updateOne('messages', 'status', '2', 'id', $message['id']);
    }
}
