<?php

namespace Access2Me\ProfileProvider;

use Access2Me\Service;

class FullContact implements ProfileProviderInterface
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
     * @return array|object
     */
    public function fetchProfile(\Access2Me\Model\Sender $sender)
    {
        try {
            $email = $sender->getSender();

            $fullContact = new Service\FullContact($this->serviceConfig);
            $personData = $fullContact->getPerson($email, Service\FullContactPersonType::EMAIL);
            
            $profile = $this->parsePersonData($personData);
            
            return $profile;
        } catch (Service\FullContactException $ex) {
            throw new ProfileProviderException('Can\'t fetch profile', 0, $ex);
        }
    }

    protected function parsePersonData($profileData)
    {
        $profile = new Profile\FullContact();
        $profile->likelihood = $profileData['likelihood'];

        // parse photos
        if (is_array($profileData['photos'])) {
            $profile->photos = $profileData['photos'];
        }

        // parse contact info
        if (isset($profileData['contactInfo']['givenName'])) {
            $profile->firstName = $profileData['contactInfo']['givenName'];
        }

        if (isset($profileData['contactInfo']['familyName'])) {
            $profile->lastName = $profileData['contactInfo']['familyName'];
        }

        if (isset($profileData['contactInfo']['fullName'])) {
            $profile->fullName = $profileData['contactInfo']['fullName'];
        }

        // parse websites
        if (isset($profileData['contactInfo']['websites'])
            && is_array($profileData['contactInfo']['websites'])
        ) {
            foreach ($profileData['contactInfo']['websites'] as $website) {
                $profile->webistes[] = $website['url'];
            }
        }

        // parse messengers
        if (isset($profileData['contactInfo']['chats'])
            && is_array($profileData['contactInfo']['chats'])
        ) {
            $messengers = [];
            // remove duplicate messengers
            foreach ($profileData['contactInfo']['chats'] as $msngr) {
                $messengers[$msngr['client'] . '_' . $msngr['handle']] = $msngr;
            }
            $profile->messengers = array_values($messengers);
        }

        // parse oganizations
        if (isset($profileData['organizations'])
            && is_array($profileData['organizations'])
        ) {
            $profile->organizations = $profileData['organizations'];
        }

        // parse demographics
        if (isset($profileData['demographics']['gender'])) {
            $profile->gender = $profileData['demographics']['gender'];
        }

        if (isset($profileData['demographics']['locationGeneral'])) {
            $profile->location = $profileData['demographics']['locationGeneral'];
        }

        if (isset($profileData['socialProfiles'])) {
            $profile->socialProfiles = $profileData['socialProfiles'];
        }
        
        return $profile;
    }
}
