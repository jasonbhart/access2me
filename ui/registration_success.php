<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php
use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\Service;

$db = new Database;
$auth = Helper\Registry::getAuth();
$user = $auth->getLoggedUser();

// user already granted access
if ($user['gmail_access_token']) {
    Helper\Http::redirect(Helper\Registry::getRouter()->getUrl('home'));
}

// send welcome email
$data = [
    'user' => $user,
    'gmail_settings_url' => Helper\Registry::getRouter()->getUrl('gmail_settings')
];

$mail = Helper\Registry::getDefaultMailer();
$mail->addAddress($user['mailbox']);
$mail->addCustomHeader('Auto-Submitted', 'auto-generated');
$mail->Subject = 'Access2.ME Registration';
$mail->Body = Helper\Registry::getTwig()->render('email/registration_success.html.twig', $data);

if (!$mail->send()) {
    Logging::getLogger()->error(
        sprintf('Can\'t send welcome email to %s: %s', $user['username'], $mail->ErrorInfo)
    );
    Helper\Http::generate500();
}

?>
<?php include 'inc/template_start.php' ?>
<?php echo Helper\Registry::getTwig()->render('registration_success.html.twig', $data); ?>
<?php include 'inc/template_end.php' ?>