<?php
/**
 * TODO: seems we don't exchange auth code to auth token
 */

require_once __DIR__ . "/boot.php";

use Access2Me\Model;

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
    
    $mesgRepo = new Model\MessageRepository($db);
    $message = $mesgRepo->getById($_GET['message_id']);

    // store auth token for the later use
    // create new or update existing sender
    $email = $message['from_email'];
    $senderRepo = new Model\SenderRepository($db);
    $sender = $senderRepo->getByEmailAndService($email, Model\SenderRepository::SERVICE_LINKEDIN);

    if ($sender == null) {
        $sender = new Model\Sender();
        $sender->setSender($email);
        $sender->setService(Model\SenderRepository::SERVICE_LINKEDIN);
    }

    // we always have new token here whether user was authenticated before or not
    $sender->setOAuthKey($accessToken);

    // fetch user's profile
    $senders = array($sender);
    $profiles = $defaultProfileProvider->getProfiles($senders, false);
    $profile = $defaultProfileProvider->getProfileByServiceId(
        $profiles,
        Model\SenderRepository::SERVICE_LINKEDIN
    );

    if ($profile == null) {
        throw new \Exception('Can\'t retrieve profile');
    }

    $defaultProfileProvider->storeProfiles($senders, $profiles);
    
    // commit changes
    $senderRepo->save($sender);
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="css/profile.css">
</head>

<center>

<br />
<h1 style="color: #ffffff;">Congratulations, Access2.me Has Validated You!</h1>

  <div class="box">
    <div class="userbox">
        <div class="pic">
            <?php if (!empty($profile->pictureUrl)): ?>
            <img src="<?php echo htmlentities($profile->pictureUrl); ?>">
            <?php endif; ?>
        </div>
    </div>
    <p>
        <span class="user">
           <?php echo "<strong>" . htmlentities($profile->fullName) . "</strong>"; ?>
           <?php echo "<br />"; ?>
           <?php echo htmlentities($profile->headline) . " (" . htmlentities($profile->industry) . ")"; ?>
           <?php echo "<br />"; ?>
           <?php echo htmlentities($profile->location); ?>

           <br />
        </span>
    </p>
  </div>

</center>

</html>

<?php

    $db->updateOne('messages', 'status', '2', 'from_email', $email);
}

?>
