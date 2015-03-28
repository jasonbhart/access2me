<?php

namespace Access2Me\Service\Auth;

use Access2Me\Helper;

/**
 * Google manager. Sends requests and is responsible for handling Google responses.
 *
 * Example:
 * $request = new Linkedin\UserAuthRequest(4);
 * $manager = new Linkedin($appConfig['services']['linkedin']);
 * $manager->requestAuth($request);
 *
 * @package Access2Me\Service\Auth
 */
class Google extends AbstractManager
{
    const SCOPE_CONTACTS = 'https://www.googleapis.com/auth/contacts.readonly';

    protected function getClient()
    {
        $client = new \Google_Client();
        $client->setClientId($this->config['client_id']);
        $client->setClientSecret($this->config['client_secret']);
        $client->setRedirectUri($this->config['callback_url']);
        return $client;
    }

    public function requestAuth(AbstractRequest $serviceRequest)
    {
        $state = md5(mt_rand());

        $client = $this->getClient();
        $client->setState($state);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $client->addScope($serviceRequest->getRequiredScopes());

        $this->storeRequest($state, $serviceRequest);

        Helper\Http::redirect($client->createAuthUrl());
    }

    public function processResponse($data)
    {
        // validate response data
        if (!isset($data['state'])) {
            throw new AuthException('Invalid state token');
        }

        if (isset($data['error'])) {
            throw new AuthException($data['error']);
        }

        if (!isset($data['code'])) {
            throw new AuthException('Invalid access code');
        }

        // get access token
        $client = $this->getClient();
        $client->authenticate($data['code']);
        $accessToken = $client->getAccessToken();
        $this->handleResult($data['state'], $accessToken);
    }
}
