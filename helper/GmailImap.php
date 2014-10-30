<?php

namespace Access2Me\Helper;

use Zend\Mail\Protocol;

class GmailImap extends Protocol\Imap
{
    protected function constructAuthString($username, $accessToken) {
        return base64_encode("user=$username\1auth=Bearer $accessToken\1\1");
    }

    public function loginOAuth2($username, $accessToken)
    {
        $authenticateParams = array(
            'XOAUTH2',
            $this->constructAuthString($username, $accessToken)
        );
        $this->sendRequest('AUTHENTICATE', $authenticateParams);

        while (true) {
            $response = "";
            $is_plus = $this->readLine($response, '+', true);
            if ($is_plus) {
                \Logging::getLogger()->addDebug('got an extra server challenge: ' . $response);
                // Send empty client response.
                $this->sendRequest('');
            } else {
                if (preg_match('/^NO /i', $response) || preg_match('/^BAD /i', $response)) {
                    throw new \Exception("got failure response: $response");
                } else if (preg_match("/^OK /i", $response)) {
                    return true;
                } else {
                    // Some untagged response, such as CAPABILITY
                }
            }
        }
    }

    public static function getImap($email, $accessToken)
    {
        $imap = new self('imap.gmail.com', '993', 'ssl');
        $imap->loginOAuth2($email, $accessToken);
        return $imap;
    }
}