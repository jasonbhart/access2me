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
    $url .= "?oauth2_access_token=" . $accessToken;

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
