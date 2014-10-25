<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Helper;

$db = new Database;

$query = "SELECT * FROM `messages` WHERE `status` = '2'";
$messages = $db->getArray($query);

if (empty($messages)) {
    die();
}

foreach ($messages AS $message) {
    $query = "SELECT `oauth_key` FROM `senders` WHERE `sender` = '" . $message['from_email'] . "' LIMIT 1";
    $key = $db->getArray($query);

    $query = "SELECT `email` FROM `users` WHERE `id` = '" . $message['user_id'] . "' LIMIT 1";
    $to = $db->getArray($query);

    $url  = "https://api.linkedin.com/v1/people/~:(";
    $url .= "first-name,";
    $url .= "last-name,";
    $url .= "email-address,";
    $url .= "headline,";
    $url .= "industry,";
    $url .= "picture-url,";
    $url .= "site-standard-profile-request,";
    $url .= "num-connections,";
    $url .= "summary,";
    $url .= "specialties,";
    $url .= "associations,";
    $url .= "interests,";
    $url .= "num-recommenders,";
    $url .= "recommendations-received,";
    $url .= "phone-numbers,";
    $url .= "im-accounts,";
    $url .= "main-address,";
    $url .= "twitter-accounts,";
    $url .= "primary-twitter-account,";
    $url .= "group-memberships,";
    $url .= "positions,";
    $url .= "location:(name))";
    $url .= "?oauth2_access_token=" . $key[0]['oauth_key'];

    $cURL = curl_init();

    curl_setopt($cURL, CURLOPT_VERBOSE, true);
    curl_setopt($cURL, CURLOPT_URL, $url);
    curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($cURL, CURLOPT_HTTPGET, true);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($cURL);
    curl_close($cURL);

    $xml = new SimpleXMLElement($result);
    $data = $xml->xpath('/person');

    $contact['first_name'] = (isset($data[0]->{'first-name'})) ? (string) $data[0]->{'first-name'} : null;
    $contact['last_name'] = (isset($data[0]->{'last-name'})) ? (string) $data[0]->{'last-name'} : null;
    $contact['email'] = (isset($data[0]->{'email-address'})) ? (string) $data[0]->{'email-address'} : null;
    $contact['headline'] = (isset($data[0]->{'headline'})) ? (string) $data[0]->{'headline'} : null;
    $contact['picture_url'] = (isset($data[0]->{'picture-url'})) ? (string) $data[0]->{'picture-url'} : null;
    $contact['profile_url'] = (isset($data[0]->{'site-standard-profile-request'}->{'url'})) ? (string) $data[0]->{'site-standard-profile-request'}->{'url'} : null;
    $contact['location'] = (isset($data[0]->{'location'}->{'name'})) ? (string) $data[0]->{'location'}->{'name'} : null;
    $contact['industry'] = (isset($data[0]->{'industry'})) ? (string) $data[0]->{'industry'} : null;
    $contact['self_summary'] = (isset($data[0]->{'summary'})) ? (string) $data[0]->{'summary'} : null;
    $contact['specialties'] = (isset($data[0]->{'specialties'})) ? (string) $data[0]->{'specialties'} : null;
    $contact['associations'] = (isset($data[0]->{'associations'})) ? (string) $data[0]->{'associations'} : null;
    $contact['interests'] = (isset($data[0]->{'interests'})) ? (string) $data[0]->{'interests'} : null;
    $contact['total_connections'] = (isset($data[0]->{'num-connections'})) ? (string) $data[0]->{'num-connections'} : null;
    $contact['total_positions'] = (isset($data[0]->{'positions'}->attributes()['total'][0])) ? (string) $data[0]->{'positions'}->attributes()['total'][0] : null;

    for ($x = 0; $x < $contact['total_positions']; $x++) {
        $contact['positions'][$x]['company'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'company'}->{'name'};
        $contact['positions'][$x]['title'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'title'};
        $contact['positions'][$x]['summary'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'summary'};
        $contact['positions'][$x]['is_current'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'is-current'};
    }

    $filter = new Filter($message['user_id'], $contact, new Database);
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
