<?php

namespace Access2Me\Service\Auth\Google;

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
        $this->handledTypes[] = 'Access2Me\Service\Auth\Google\UserAuthRequest';
    }

    public function handle($serviceRequest, $serviceResponse)
    {
        $accessToken = $serviceResponse;

        $db = new \Database();
        $userRepo = new Model\UserRepository($db);
        $user = $userRepo->getById($serviceRequest->userId);

        if (!$user) {
            \Logging::getLogger()->error('Invalid userId: ' . $serviceRequest->userId);
            return;
        }

        // Store token in the DB
        $user['gmail_access_token'] = $accessToken;
        $userRepo->save($user);
        Helper\Http::redirect($serviceRequest->redirectUrl);
    }
}
