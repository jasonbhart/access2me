<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Helper;
use Access2Me\Model;


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
        $data = [
            'recipient_email' => $message['to_email'],
            'sender_email' => $message['reply_email'],
            'sender_verification_url' => Helper\Registry::getRouter()->getUrl('sender_verification', ['message_id' => $message['id']])
        ];

        $mail = Helper\Registry::getDefaultMailer();
        $mail->addAddress($message['reply_email']);
        $mail->addCustomHeader('Auto-Submitted', 'auto-replied');
        $mail->Subject = 'Access2.ME Verification';
        $mail->Body = Helper\Registry::getTwig()->render('email/sender_verification.html.twig', $data);

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

$notVerified = $mesgRepo->findByStatus(Model\MessageRepository::STATUS_NOT_VERIFIED);

// request verification from the senders
foreach ($notVerified AS $message) {
    $senders = $senderRepo->getByEmail($message['from_email']);

    if (!$senders) {
        $authRequest = new AuthRequest($appConfig, $db);
        $authRequest->request($message);
    } else {
        $message['status'] = Model\MessageRepository::STATUS_VERIFIED;
        $mesgRepo->save($message);
    }
}
