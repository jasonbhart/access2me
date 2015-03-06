<?php

namespace Access2Me\Service\Auth\Linkedin;

use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\Service;
use Access2Me\Service\Auth;

class UserAuthHandler extends Auth\AbstractHandler
{
    private $appConfig;

    public function __construct($appConfig)
    {
        $this->appConfig = $appConfig;
        $this->handledTypes[] = 'Access2Me\Service\Auth\Linkedin\UserAuthRequest';
    }

    public function handle($linkedinRequest, $linkedinResponse)
    {
        $accessToken = $linkedinResponse['access_token'];

        $db = new \Database();
        $userRepo = new Model\UserRepository($db);
        $user = $userRepo->getById($linkedinRequest->userId);

        if (!$user) {
            \Logging::getLogger()->error('Invalid userId: ' . $linkedinRequest->userId);
            return;
        }

        // we always have new token here whether user was authenticated before or not
        $tokenRefresher = new Service\TokenRefresher($this->appConfig);
        $expTime = $tokenRefresher->extendExpireTime(Service\Service::LINKEDIN, $accessToken);

        // fill token record
        $token = new Model\User\LinkedinToken();
        $token->setToken($accessToken);
        $token->setCreatedAt($expTime['created_at']);
        $token->setExpiresAt($expTime['expires_at']);
        $user['linkedin_access_token'] = $token;

        $userRepo->save($user);

        Helper\FlashMessage::add('You have successfully linked LinkedIn account', Helper\FlashMessage::SUCCESS);

        Helper\Http::redirect($linkedinRequest->redirectUrl);
        exit;
    }
}
