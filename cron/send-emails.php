<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Model;
use Access2Me\Helper;
use Access2Me\ProfileProvider;

$db = new Database;

$query = "SELECT * FROM `messages` WHERE `status` = '2'";
$messages = $db->getArray($query);

if (empty($messages)) {
    die();
}

foreach ($messages AS $message) {
    $query = "SELECT `email` FROM `users` WHERE `id` = '" . $message['user_id'] . "' LIMIT 1";
    $to = $db->getArray($query);

    $repo = new Model\SenderRepository($db);
    $senders = $repo->getByEmail($message['from_email']);

    $providers = array(
        Model\SenderRepository::SERVICE_FACEBOOK => new ProfileProvider\Facebook($facebookAuth),
        Model\SenderRepository::SERVICE_LINKEDIN => new ProfileProvider\Linkedin($linkedinAuth),
        Model\SenderRepository::SERVICE_TWITTER => new ProfileProvider\Twitter($twitterAuth)
    );
    
    $provider = new Helper\SenderProfileProvider($providers);
    $profile = $provider->getProfile($senders);

    if ($profile == null) {
        $errMsg = sprintf(
            'Can\'t retrieve profile of %s (message id: %d)',
            $message['email_from'],
            $message['id']
        );
        Logging::getLogger()->error($errMsg);
        continue;
    }

    // FIXME: Currently only for linkedin since only it was tested with Filter
    if (!isset($profile['services'][Model\SenderRepository::SERVICE_LINKEDIN])) {
        continue;
    }
    
    $contact = $profile['services'][Model\SenderRepository::SERVICE_LINKEDIN]['profile'];

    $filter = new Filter($message['user_id'], $contact, $db);
    $filter->processFilters();
    if ($filter->status === true) {
        $append = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head> <body><table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"380\"><tr><td><img src=\"" . $contact['picture_url'] . "\" nosend=\"1\" border=\"0\" width=\"80\" height=\"80\" alt=\"photo\"></td><td><table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"380\"><tr><td><tr><td width=\"7\"></td><td width=\"370\" align=\"left\"><a href=\"" . $contact['profile_url'] . "\"><img src=\"http://app.access2.me/images/linkedin-22x17-bw.gif\" nosend=\"1\" border=\"0\" width=\"22\" height=\"17\" alt=\"LinkedIn\"></a><img src=\"http://app.access2.me/images/clear.gif\" nosend=\"1\" border=\"0\" width=\"3\" height=\"1\"><span style=\"text-align: right; margin-top: 0px; color: #808080; font-size: 8pt; font-weight: bold; font-family: 'Calibri', sans-serif;\"><font size=\"1\" color=\"#808080\">|</font>&nbsp; <a style=\"text-decoration: none; color: #808080\" href=\"mailto:" . $contact['email'] . "\"><font color=\"#808080\">email</font></a></span></td><td width=\"10\"><br></td><span style=\"line-height: 2px; font-size: 2px;\">&nbsp;<br></span></tr></table><table cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td width=\"13\"></td><td><span style=\"text-align: left; margin-top: 0px; color: #000000; font-size: 8pt; font-weight: normal; font-family: 'Arial', sans-serif;\"><span style=\"text-align: left; color: #000000; font-family: Arial; font-size: 12pt; font-style: bold; font-weight: normal;\">" . $contact['first_name'] . " " . $contact['last_name'] . "<br /></span><span style=\"text-align: left; color: #000000; font-family: Arial; font-size: 8pt; font-style: normal; font-weight: normal;\">" . $contact['headline'] . "<br> " . $contact['location'] . "</span></span></td></tr></td></tr></table></td></tr></table><table cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td style=\"padding-left: 0px;\"><span style=\"color: #8A8A8A; font-family: 'Calibri', sans-serif; font-size: 8pt; font-weight: regular;\"></span></td></tr></table><table cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td style=\"padding-left: 0px;\"></td></tr></table></body><style> a {color: #808080;} </style><span style=\"background-color: #0000ff; font-weight: bold; color: #ffffff;\">&nbsp;&nbsp;&nbsp;This contact has been verified by Access2.me&nbsp;&nbsp;&nbsp;</span><br /><br />";

        $body = Helper\Email::getMessageBody(
            $message['header'] . "\r\n" . $message['body']
        );

        $fromName = $contact['first_name'] . " " . $contact['last_name'];
    
        // build our info header
        $altInfoBody = new \ezcMailText('This is the body in plain text for non-HTML mail clients');
        $infoBody = new \ezcMailText($append);
        $infoBody->subType = 'html';
        $info = new \ezcMailMultipartAlternative($altInfoBody, $infoBody);

        // join our header and content of the original message
        $newBody = new \ezcMailMultipartMixed($info, $body);

        // build new message
        $newMail = new \ezcMail();
        $newMail->from = new \ezcMailAddress(
            'noreply@access2.me',
            $fromName
        );
        $newMail->to = array(new \ezcMailAddress($to[0]['email']));
        $newMail->setHeader('Reply-To', $message['reply_email']);
        $newMail->setHeader('X-Mailer', '');
        $newMail->subject = $message['subject'];
        $newMail->body = $newBody;

        // do not include User-Agent header in the mail
        $newMail->appendExcludeHeaders(array('User-Agent'));

        // send new message
        $smtp = new \ezcMailSmtpTransport(
            'mail.access2.me',
            'noreply@access2.me',
            'access123',
            587
        );
        $smtp = new \ezcMailSmtpTransport(
            'imap.loc'
        );
        $smtp->senderHost = 'access2.me';
        $smtp->options->connectionType = \ezcMailSmtpTransport::CONNECTION_TLS;

        try {
            $smtp->send($newMail);
            $db->updateOne('messages', 'status', '3', 'id', $message['id']);
        } catch (Exception $ex) {
            Logging::getLogger()->error(
                'Can\'t send message: ' . $message['id'], 
                array('exception' => $ex)
            );
        }

    } else {
        $db->updateOne('messages', 'status', '4', 'id', $message['id']);
    }
}
