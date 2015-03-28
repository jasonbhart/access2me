<?php

namespace Access2Me\Service\Auth;

use Access2Me\Helper;

/**
 * Linkedin manager. Sends requests and is responsible for handling Linkedin responses.
 *
 * Example:
 * $request = new Linkedin\UserAuthRequest(4);
 * $manager = new Linkedin($appConfig['services']['linkedin']);
 * $manager->requestAuth($request);
 *
 * @package Access2Me\Service\Auth
 */
class Linkedin extends AbstractManager
{
    const SCOPE_BASIC_PROFILE = 'r_basicprofile';
    const SCOPE_CONTACT_INFO = 'r_contactinfo';

    public function requestAuth(AbstractRequest $serviceRequest)
    {
        $state = md5(mt_rand());
        $linkedin = new Helper\Linkedin($this->config);
        $this->storeRequest($state, $serviceRequest);
        $url = $linkedin->getLoginUrl(
            $state,
            $serviceRequest->getRequiredScopes(),
            $this->config['callback_url']
        );
        Helper\Http::redirect($url);
    }

    public function processResponse($data)
    {
        // validate response data
        if (!isset($data['state'])) {
            throw new AuthException('Invalid state token');
        }

        if (isset($data['error'])) {
            $message = $data['error'] . (isset($data['error_description']) ? ': ' . $data['error_description'] : '');
            throw new AuthException($message);
        }

        if (!isset($data['code'])) {
            throw new AuthException('Invalid access code');
        }

        // get access token
        $linkedin = new Helper\Linkedin($this->config);
        $result = $linkedin->getAccessToken($data['code'], $this->config['callback_url']);
        $this->handleResult($data['state'], $result);
    }
}
