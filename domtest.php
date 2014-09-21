<?php

require_once __DIR__ . "/boot.php";

$mail = new PHPMailer;

$mail->isSMTP();
$mail->Host = 'mail.access2.me';
$mail->SMTPAuth = true;
$mail->Username = 'noreply@access2.me';
$mail->Password = 'access123';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->From = 'noreply@access2.me';
$mail->FromName = 'Access2 Me';
$mail->addAddress('jason@leadwrench.com', 'Jason');
$mail->addReplyTo('dom@edgeprod.com', 'Dom');
$mail->XMailer = ' ';
$mail->Hostname = 'access2.me';

$mail->isHTML(true);

$mail->Subject = 'Access2.ME Email Full Test';
$mail->Body    = "Canadensis, Pennsylvania (CNN) -- Hundreds of law enforcement officers searched Saturday for the suspect in the slaying of a Pennsylvania state trooper -- hours after authorities appeared to be closing in on the self-taught survivalist and bursts of gunfire were heard near the man's home in the Poconos Mountains.";
$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

if(!$mail->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'Message has been sent';
}
