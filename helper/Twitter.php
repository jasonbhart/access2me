<?php

namespace Access2Me\Helper;

class TwitterException extends \Exception
{
//    public function __construct($message, $code, $previous)
//    {
//        parent::__construct($message, $code, $previous);
//    }
    
    public function __toString()
    {
        return 'Twitter exception: ' . $this->message;
    }
}

/**
 * @link https://dev.twitter.com/web/sign-in/implementing
 */
class Twitter
{
    /**
     * @var \tmhOAuth
     */
    protected $tmhOAuth;

    /**
     * @var array
     */
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    protected function getOAuth()
    {
        if (!$this->tmhOAuth) {
            $this->tmhOAuth = new \tmhOAuth($this->config);
        }

        return $this->tmhOAuth;
    }

    /**
     * Converts twitter response to TwitterExcetion
     * 
     * @param string|array $data
     * @return \Access2Me\Helper\TwitterException
     */
    protected function responseToException($data)
    {
        $message = 'Unknown exception';
        $code = 0;

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        // get message and code from response
        if (is_array($data['errors']) && count($data['errors']) > 0) {

            $error = $data['errors'][0];

            if (isset($error['message'])) {
                $message = $error['message'];
            }

            if (isset($error['code'])) {
                $code = intval($error['code']);
            }
        }

        return new TwitterException($message, $code);
    }

    /**
     * Requests `Request Token` to be used for user authentication
     * 
     * @return array Request token
     * @throws TwitterException
     */
    public function getRequestToken($callbackUrl)
    {
        $oauth = $this->getOAuth();

        // make request
        $code = $oauth->apponly_request(array(
            'without_bearer' => true,
            'method' => 'POST',
            'url' => $oauth->url('oauth/request_token', ''),
            'params' => array(
                'oauth_callback' => $callbackUrl
            )
        ));

        $response = $oauth->response['response'];
        
        // validate and convert response
        if ($code != 200) {
            throw $this->responseToException($response);
        }
        
        $result = $oauth->extract_params($response);

        // twitter confirmed callback ?
        if ($result['oauth_callback_confirmed'] !== 'true') {
            throw new TwitterException('Callback not confirmed');
        }

        return array(
            'oauth_token' => $result['oauth_token'],
            'oauth_token_secret' => $result['oauth_token_secret']
        );
    }

    /**
     * Get url for user authentication
     * 
     * @param array $requestToken with oauth_token key
     * @return string
     */
    public function getAuthUrl($requestToken)
    {
        $oauth = $this->getOAuth();
        return $oauth->url('oauth/authenticate', '')
            . '?oauth_token=' . $requestToken['oauth_token'];
    }

    /**
     * Checks if this is auth response
     * 
     * @param array $response
     * @return boolean
     */
    public function isAuthResponse(array $response)
    {
        return isset($response['oauth_token']);
    }

    /**
     * Validates that current response corresponds to saved token
     * 
     * @param array $response
     * @param array $savedToken
     * @return boolean
     */
    public function isValidResponse(array $response, array $savedToken)
    {
        $valid = isset($response['oauth_token']) && isset($savedToken['oauth_token'])
            && $response['oauth_token'] == $savedToken['oauth_token'];

        return $valid;
    }
    
    /**
     * Get verification code from request
     * 
     * @param array $response $_GET or $_REQUEST
     * @return string|null
     */
    public function getVerificationCode(array $response)
    {
        return !empty($response['oauth_verifier'])
            ? $response['oauth_verifier'] : null;
    }

    /**
     * Upgrades `Request Token` to an `Access Token`
     * 
     * @param array $token array('oauth' => array(...), 'verifier' => '..code..')
     * @param string $verificationCode
     * @return array
     */
    public function upgradeToAccessToken($token, $verificationCode)
    {
        $oauth = $this->getOAuth();

        $config = array_merge(
            $this->config,
            array(
                'token' => $token['oauth_token'],
                'secret' => $token['oauth_token_secret'],
            )
        );

        $oauth->reconfigure($config);

        $code = $oauth->user_request(array(
            'method' => 'POST',
            'url' => $oauth->url('oauth/access_token', ''),
            'params' => array(
                'oauth_verifier' => $verificationCode)
            )
        );

        $response = $oauth->response['response'];
        
        if ($code != 200) {
            throw $this->responseToException($response);
        }

        $result = $oauth->extract_params($response);

        return array(
            'oauth_token' => $result['oauth_token'],
            'oauth_token_secret' => $result['oauth_token_secret']
        );
    }

    /**
     * Returns user's contact info
     * 
     * @param array $token
     * @throws TwitterException
     */
    public function getContactInfo($token)
    {
        $oauth = $this->getOAuth();

        $config = array_merge(
            $this->config,
            array(
                'token' => $token['oauth_token'],
                'secret' => $token['oauth_token_secret'],
            )
        );

        $oauth->reconfigure($config);

        $code = $oauth->user_request(array(
            'method' => 'GET',
            'url' => $oauth->url('1.1/account/verify_credentials', 'json')
        ));

        $response = $oauth->response['response'];
        
        if ($code != 200) {
            throw $this->responseToException($response);
        }

        $data = json_decode($response, true);
        
        $contact = array(
            'first_name' => $data['name'],
            'last_name' => '',
            'headline' => null,
            'industry' => null,
            'location' => $data['location'],
            'picture_url' => $data['profile_image_url']
        );
        
        return $contact;
    }
}
