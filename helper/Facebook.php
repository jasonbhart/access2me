<?php

namespace Access2Me\Helper;

use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Facebook\GraphUser;

/**
 * Facebook internally requires appId/secret,
 * but for some methods there is no way to specify them.
 * So you need to call
 * FacebookSession::setDefaultApplication($appId, $appSecret);
 * before calling methods of this class.
 */
class Facebook
{
    protected $session;

    /**
     * @param string|Facebook\FacebookSession $oauthToken oauth token or session object
     */
    public function __construct($oauthToken)
    {
        if ($oauthToken instanceof FacebookSession) {
            $this->session = $oauthToken;
        } else {
            $this->session = new FacebookSession($oauthToken);
        }
    }

    public function getSession()
    {
        return $this->session;
    }
    
    /**
     * Validates facebook session
     * 
     * @return boolean
     */
    public function validate()
    {
        return $this->getSession()->validate();
    }

    /**
     * Returns data required for profile page
     */
    public function getProfile()
    {
        $request = new FacebookRequest($this->getSession(), 'GET', '/me');
        $gobject = $request->execute()->getGraphObject();
        
        return $gobject;
    }

    public function getPictureUrl()
    {
        $request = new FacebookRequest(
            $this->getSession(),
            'GET',
            '/me/picture',
            array(
                'redirect' => false,
                'height' => '200',
                'type' => 'normal',
                'width' => '200',
            )
        );
        
        $response = $request->execute();
        $graphObject = $response->getGraphObject();
        
        return $graphObject->getProperty('url');
    }

    /**
     * Formats location
     * 
     * @param \Facebook\GraphLocation $location
     * @return string
     */
    public static function formatLocation(\Facebook\GraphLocation $location)
    {
        $locationArr = array();
        if ($location->getCountry()) {
            $locationArr[] = $location->getCountry();
        }

        if ($location->getState()) {
            $locationArr[] = $location->getState();
        }

        if ($location->getCity()) {
            $locationArr[] = $location->getCity();
        }

        if ($location->getStreet()) {
            $locationArr[] = $location->getStreet();
        }

        if ($location->getZip()) {
            $locationArr[] = $location->getZip();
        }

        if ($location->getLatitude() !== null && $location->getLongitude() !== null) {
            $locationArr[] = strval($location->getLatitude())
                . ',' . strval($location->getLongitude());
        }

        return implode(', ', $locationArr);
    }

    /**
     * Check that user allowed required permission
     * 
     * @param array $requiredPerms required permissions
     * @return boolean
     */
    public function validatePermissions($requiredPerms)
    {
        $request = new FacebookRequest($this->getSession(), 'GET', '/me/permissions');
        $data = $request->execute()->getGraphObject();

        $permissions = array();
        foreach ($data->getPropertyNames() as $name) {
            $perm = $data->getProperty($name);
            if ($perm->getProperty('status') == 'granted') {
                $permissions[] = $perm->getProperty('permission');
            }
        }

        $missing = array_diff($requiredPerms, $permissions);

        return count($missing) == 0;
    }
}
