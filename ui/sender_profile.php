<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

<?php
use Access2Me\Helper;
use Access2Me\Model;

// controller like function :)
function showSendersProfile()
{
    $data = array();
    $email = isset($_GET['email']) ? $_GET['email'] : null;
    
    $db = new Database;

    // check if current user has messages from the sender
    $user = (new Helper\Auth($db))->getLoggedUser();
    $mesgRepo = new Model\MessageRepository($db);
    $messages = $mesgRepo->findByUserAndSender($user['id'], $email);
   
    if (empty($messages)) {
        $data['error'] = 'No such sender';
        return $data;
    }

    // get all services for the sender
    $senderRepo = new Model\SenderRepository($db);
    $senders = $senderRepo->getByEmail($email);

    if (empty($senders)) {
        $data['error'] = 'No such sender';
        return $data;
    }

    // get all profiles of the sender
    $defaultProfileProvider = Helper\Registry::getProfileProvider();
    try {
        $data['profiles'] = [
            'sender' => $senders[0]->getSender(),
            'services' => $defaultProfileProvider->getProfiles($senders)
        ];
    } catch (Exception $ex) {
        $errMsg = 'Can\'t retrieve profile of ' . $email;
        Logging::getLogger()->info($errMsg);
        $data['error'] = 'Unfortunately we can\'t retrieve sender\'s profile right now.';
        return $data;
    }

    return $data;
}

try {
    $data = showSendersProfile();

    extract($data);
    // render data
    require_once '../views/sender_profile.html';
} catch (\Exception $ex) {
    Logging::getLogger()->error($ex->getMessage(), array('exception' => $ex));
    Helper\Http::generate500();
}
?>

<?php include 'inc/page_footer.php'; ?>
<?php include 'inc/template_scripts.php'; ?>
<?php include 'inc/template_end.php'; ?>