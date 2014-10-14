<?php

namespace Access2Me\Helper;

use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Facebook\GraphUser;

class Facebook
{
    /**
     * Collect data for showing to user after successful authentication
     * 
     * @param Facebook\FacebookSession $session
     */
    public static function getContactInfo(FacebookSession $session)
    {
        $request = new FacebookRequest($session, 'GET', '/me');
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
        $request = new FacebookRequest(
            $session,
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

        $contact['picture_url'] = $graphObject->getProperty('url');
        
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
     * @return boolean
     */
    public static function validatePermissions(FacebookSession $session, $requiredPerms)
    {
        $request = new FacebookRequest($session, 'GET', '/me/permissions');
        $data = $request->execute()->getGraphObject();

        $permissions = array();
        foreach ($data->getPropertyNames() as $name) {
            $perm = $data->getProperty($name);
            $permissions[] = $perm->getProperty('permission');
        }

        $missing = array_diff($requiredPerms, $permissions);

        return count($missing) == 0;
    }
    
}