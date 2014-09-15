<?php

require_once __DIR__ . "/../boot.php";

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

    $url = "https://api.linkedin.com/v1/people/~:(first-name,last-name,email-address,headline,industry,picture-url,site-standard-profile-request,location:(name))?oauth2_access_token=" . $key[0]['oauth_key'];

    $cURL = curl_init();

    curl_setopt($cURL, CURLOPT_VERBOSE, true);
    curl_setopt($cURL, CURLOPT_URL, $url);
    curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($cURL, CURLOPT_HTTPGET, true);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($cURL);
    curl_close($cURL);

    $parser = xml_parser_create();
    xml_parse_into_struct($parser, $result, $values, $index);
    xml_parser_free($parser);

    foreach ($values AS $value) {
        switch ($value['tag']) {
            case 'FIRST-NAME':
                $contact['first_name'] = (string) $value['value'];
                break;
            case 'LAST-NAME':
                $contact['last_name'] = (string) $value['value'];
                break;
            case 'EMAIL-ADDRESS':
                $contact['email'] = (string) $value['value'];
                break;
            case 'HEADLINE':
                $contact['headline'] = (string) $value['value'];
                break;
            case 'PICTURE-URL':
                $contact['picture_url'] = (string) $value['value'];
                break;
            case 'URL':
                $contact['url'] = (string) $value['value'];
                break;
            case 'NAME':
                $contact['location'] = (string) $value['value'];
                break;
            case 'INDUSTRY':
                $contact['industry'] = (string) $value['value'];
                break;
        }
    }

    $append  = 'Access2.me has verified this contact. Their details are below:';
    $append .= "<br /><br />";
    $append .= '<img src="' . $contact['picture_url'] . '">';
    $append .= "<br />";
    $append .= "<strong>" . $contact['first_name'] . " " . $contact['last_name'] . "</strong>";
    $append .= "<br />";
    $append .= $contact['headline'] . " (" . $contact['industry'] . ")";
    $append .= "<br />";
    $append .= $contact['location'];
    $append .= "<br /><br />";

    $params = array(
        'host'     => 'smtp.spamarrest.com',
        'port'     => 587,
        'user'     => 'dmerenda',
        'password' => 'drm+jlm'
    );

    $smtp = new SMTP($params);

    $smtp->sendEmail(
        $to[0]['email'],
        $contact['first_name'] . " " . $contact['last_name'] . " (via Access2.me)",
        $message['from_email'],
        $message['subject'],
        $append . $message['body'],
        true,
        null
    );

    $db->updateOne('messages', 'status', '3', 'id', $message['id']);
}
