<?php

namespace Access2Me\Helper;

class Google
{
    protected static function getTokenInfo($accessToken)
    {
        $url = 'https://www.googleapis.com/oauth2/v1/tokeninfo';
        $fields = array('access_token' => $accessToken);
        $query = http_build_query($fields, '', '&');

        $cURL = curl_init();

        curl_setopt($cURL, CURLOPT_VERBOSE, true);
        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURL, CURLOPT_POST, 1);
        curl_setopt($cURL, CURLOPT_POSTFIELDS, $query);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($cURL);
        curl_close($cURL);

        return json_decode($result, true);
    }

    public static function isTokenValid($accessToken)
    {
        $info = self::getTokenInfo($accessToken);
        return !isset($info['error']);
    }

    public static function requestAuthToken($refreshToken)
    {
        $url = 'https://accounts.google.com/o/oauth2/token';
        $fields = array(
            'client_id' => '523467224320-5evqo2ovdnqqntulu3531298cp8hfh12.apps.googleusercontent.com',
            'client_secret' => '8s74XEEucknNhYb6keO0yzBw',
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'

        );
        $query = http_build_query($fields, '', '&');

        $cURL = curl_init();

        curl_setopt($cURL, CURLOPT_VERBOSE, true);
        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURL, CURLOPT_POST, 1);
        curl_setopt($cURL, CURLOPT_POSTFIELDS, $query);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($cURL);
        curl_close($cURL);

        $json = json_decode($result, true);

        if (!isset($json['access_token'])) {
            throw new \Exception('Can\'t get access token');
        }
        
        return $json['access_token'];
    }
}
