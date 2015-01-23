<?php

namespace Access2Me\ProfileProvider;

use Access2Me\Helper;
use Access2Me\Model\Profile;

class Twitter implements ProfileProviderInterface
{
    private $serviceConfig;

    /**
     * @param array $serviceConfig
     */
    public function __construct($serviceConfig)
    {
        $this->serviceConfig = $serviceConfig;
    }

    /**
     * @param \Access2Me\Model\Sender $sender
     * @return array
     */
    public function fetchProfile(\Access2Me\Model\Sender $sender)
    {
        try {
            $twitter = new Helper\Twitter($this->serviceConfig);
            $data = $twitter->getUserRepresentation($sender->getOAuthKey());
            $profile = $this->convertToProfile($data);
            return $profile;
        } catch (Helper\TwitterException $ex) {
            throw new ProfileProviderException('Can\'t fetch profile', 0, $ex);
        }
    }

    protected function convertToProfile($data)
    {
        // FIXME: Twitter doesn't provide email
        $profile = new Profile\Profile();
        $profile->fullName = $data['name'];
        $profile->summary = $data['description'];
        $profile->location = $data['location'];
        $profile->pictureUrl = $data['profile_image_url'];
        $profile->profileUrl = Helper\Twitter::getProfileUrl(intval($data['id']));
        
        return $profile;
    }
}
