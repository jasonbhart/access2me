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
     * Collect data for showing to user after successful authentication
     */
    public function getContactInfo()
    {
        $request = new FacebookRequest($this->getSession(), 'GET', '/me');
        $userProfile = $request->execute()->getGraphObject(GraphUser::className());

        $contact = array(
            'first_name' => $userProfile->getFirstName(),
            'last_name' => $userProfile->getLastName(),
            'headline' => null,
            'industry' => null
        );

        $contact['location'] = $userProfile->getLocation() !== null
            ? self::formatLocation($userProfile->getLocation()) : null;

        // fetch picture
        $contact['picture_url'] = $this->getPictureUrl();
        
        return $contact;
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

    /**
     * Returns data required for profile page
     */
    public function getProfile()
    {
        $request = new FacebookRequest($this->getSession(), 'GET', '/me');
        $gobject = $request->execute()->getGraphObject();

        // get profile data
        $profile = array(
            'name' => $gobject->getProperty('name'),
            'email' => $gobject->getProperty('email'),
            'biography' => $gobject->getProperty('bio'),
            'birthday' => $gobject->getProperty('birthday'),
            'gender' => $gobject->getProperty('gender'),
            'link' => $gobject->getProperty('link'),
            'location' => $gobject->getProperty('location'),
            'website' => $gobject->getProperty('website'),
            'work' => $gobject->getProperty('work'),
            'picture_url' => $this->getPictureUrl()
        );
        
        return $profile;
    }
    
}
