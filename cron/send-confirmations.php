<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\Service;


class AuthRequest
{
    protected $appConfig;

    /**
     * @var \Database
     */
    protected $db;

    public function __construct($appConfig, $db)
    {
        $this->appConfig = $appConfig;
        $this->db = $db;
    }

    protected function send($message)
    {
        $append  = htmlspecialchars($message['to_email']) . ' has requested that you verify your identity before communicating with them.';
        $append .= "<br /><br />";
        $append .= 'Please click <a href="' . $this->appConfig['siteUrl'] . '/verify.php?message_id=' . $message['id'] . '">here</a>'
                . ' to verify your identity by logging into your LinkedIn, Facebook, or Twitter account.';

        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = $this->appConfig['smtp']['host'];
        $mail->SMTPAuth = $this->appConfig['smtp']['auth'];
        if ($mail->SMTPAuth) {
            $mail->Username = $this->appConfig['smtp']['username'];
            $mail->Password = $this->appConfig['smtp']['password'];
        }
        $mail->SMTPSecure = $this->appConfig['smtp']['encryption'];
        $mail->Port = $this->appConfig['smtp']['port'];

        $mail->From = $this->appConfig['email']['no_reply'];
        $mail->FromName = 'Access2.ME';
        $mail->addAddress($message['reply_email']);
        //$mail->XMailer = '';
        $mail->Hostname = 'access2.me';
        $mail->addCustomHeader('Auto-Submitted', 'auto-replied');

        $mail->isHTML(true);

        $mail->Subject = 'Access2.ME Verification';
        $mail->Body    = $append;

        if (!$mail->send()) {
            throw new \Exception($mail->ErrorInfo);
        }
    }

    public function request($message)
    {
        $sender = $message['from_email'];

        // did we already requested authentication ?
        $isAuthRequested = Helper\SenderAuthentication::isRequested($sender, $this->db);
        if ($isAuthRequested) {
            return true;
        }

        try {
            $this->send($message);
            Helper\SenderAuthentication::setRequested($sender, $this->db);
            return true;
        } catch (\Exception $ex) {
            Logging::getLogger()->error(
                'Auth request error',
                ['context' => $ex]
            );
        }

        return false;
    }
}


$db = new Database;
$mesgRepo = new Model\MessageRepository($db);
$senderRepo = new Model\SenderRepository($db);

$tokenRefresher = new Service\TokenRefresher($appConfig);
$notVerified = $mesgRepo->findByStatus(Model\MessageRepository::STATUS_NOT_VERIFIED);

foreach ($notVerified AS $message) {
    $senders = $senderRepo->getByEmail($message['from_email']);

    // remove invalid tokens
    $tokens = [];
    foreach ($senders as $sender) {

        // process only expired tokens
        if ($tokenRefresher->isExpired($sender)) {

            // try extend lifetime of expire token and save it to he storage
            if ($tokenRefresher->extendLifetime($sender)) {
                $senderRepo->save($sender);
                $tokens[] = $sender;
            } else {
                $senderRepo->delete($sender->getId());
            }
        }
    }

    // send auth request if we don't have valid tokens
    if (!$tokens) {
        $authRequest = new AuthRequest($appConfig, $db);
        if ($authRequest->request($message)) {
            $message['status'] = Model\MessageRepository::STATUS_VERIFY_REQUESTED;
            $mesgRepo->save($message);
        }
    } else {
        $message['status'] = Model\MessageRepository::STATUS_VERIFIED;
        $mesgRepo->save($message);
    }
}
