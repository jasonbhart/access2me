<?php

namespace Access2Me\Service\Auth\Google;

use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\Service;
use Access2Me\Service\Auth;

class SenderAuthHandler extends Auth\AbstractHandler
{
    private $appConfig;

    public function __construct($appConfig)
    {
        $this->appConfig = $appConfig;
        $this->handledTypes[] = 'Access2Me\Service\Auth\Google\SenderAuthRequest';
    }

    public function handle($serviceRequest, $serviceResponse)
    {
        $accessToken = $serviceResponse;

        $db = new \Database();
        $mesgRepo = new Model\MessageRepository($db);
        $message = $mesgRepo->getById($serviceRequest->messageId);

        if (!$message) {
            \Logging::getLogger()->error('Invalid messageId: ' . $serviceRequest->messageId);
            return;
        }

        // store auth token for the later use
        // create new or update existing sender
        $email = $message['from_email'];
        $senderRepo = new Model\SenderRepository($db);
        $sender = $senderRepo->getByEmailAndService($email, Service\Service::GOOGLE);

        if ($sender == null) {
            $sender = new Model\Sender();
            $sender->setSender($email);
            $sender->setService(Service\Service::GOOGLE);
        }

        // we always have new token here whether user was authenticated before or not
        $sender->setOAuthKey($accessToken);
        $tokenRefresher = new Service\TokenRefresher($this->appConfig);
        $expTime = $tokenRefresher->extendExpireTime($sender->getService(), $accessToken);
        $sender->setCreatedAt($expTime['created_at']);
        $sender->setExpiresAt($expTime['expires_at']);

        // commit changes
        $senderRepo->save($sender);

        // sender is verified, mark message as allowed to be processed (filtering, sending to recipient)
        $message['status'] = Model\MessageRepository::STATUS_VERIFIED;
        $mesgRepo->save($message);

        echo Helper\Template::render($this->appConfig['projectRoot'] . '/views/auth_completed.html', ['email' => $email]);
    }
}
