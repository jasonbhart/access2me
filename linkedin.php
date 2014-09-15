<?php

require_once __DIR__ . "/boot.php";

$db = new Database;

if (!$_GET['code']) {
    header('Location: https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=75dl362rayg47t&state=ECEEFWF45453sdffef424&redirect_uri=' . $localUrl . '/linkedin.php%3Fmessage_id%3d' . $_GET['message_id']);
} else {
    $url = "https://www.linkedin.com/uas/oauth2/accessToken?grant_type=authorization_code&code=" . $_GET['code'] . "&redirect_uri=" . $localUrl . "/linkedin.php%3Fmessage_id%3d" . $_GET['message_id'] . "&client_id=75dl362rayg47t&client_secret=eCxKfjOpunoO9rSj";

    $cURL = curl_init();

    curl_setopt($cURL, CURLOPT_VERBOSE, true);
    curl_setopt($cURL, CURLOPT_URL, $url);
    curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($cURL, CURLOPT_HTTPGET, true);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json'
    ));

    $result = curl_exec($cURL);
    curl_close($cURL);
    $json = json_decode($result, true);

    $accessToken = (string) $json['access_token'];

    $url = "https://api.linkedin.com/v1/people/~:(first-name,last-name,email-address,headline,industry,picture-url,site-standard-profile-request,location:(name))?oauth2_access_token=" . $accessToken;

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

    $query = "SELECT `from_email` FROM `messages` WHERE `id` = '" . $_GET['message_id'] . "' LIMIT 1;";
    $message = $db->getArray($query);

    $db->insert(
        'senders',
        array(
            'sender',
            'service',
            'oauth_key'
        ),
        array(
            $message[0]['from_email'],
            '1',
            $accessToken
        ),
        true
    );
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="css/profile.css">
</head>

<center>

<br />
<h1>Congratulations, Access2.me Has Validated You!</h1>
<h3>Your Email has been sent.</h3>

  <div class="box">
    <div class="userbox">
      <div class="pic"><img src="<?php echo $contact['picture_url']; ?>"></div>
    </div>
    <p>
        <span class="user">
           <?php echo "<strong>" . $contact['first_name'] . " " . $contact['last_name'] . "</strong>"; ?>
           <?php echo "<br />"; ?>
           <?php echo $contact['headline'] . " (" . $contact['industry'] . ")"; ?>
           <?php echo "<br />"; ?>
           <?php echo $contact['location']; ?>

           <br />
        </span>
    </p>
  </div>

</center>

</html>

<?php

    $db->updateOne('messages', 'status', '2', 'from_email', $message[0]['from_email']);
}

?>
