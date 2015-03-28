<?php

namespace Access2Me\ProfileProvider;


class Google implements ProfileProviderInterface
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
            $client = new \Google_Client();
            $client->setClientId($this->serviceConfig['client_id']);
            $client->setClientSecret($this->serviceConfig['client_secret']);
            $client->setAccessToken($sender->getOAuthKey());
            $gplus = new \Google_Service_Plus($client);
            $data = $gplus->people->get('me');
            $profile = $this->convertToProfile($data);
            var_dump($profile);
            return $profile;
        } catch (\Google_Exception $ex) {
            throw new ProfileProviderException('Can\'t fetch profile', 0, $ex);
        }
    }

    protected function convertToProfile(\Google_Service_Plus_Person $data)
    {
        $profile = new Profile\Google();
        $profile->id = $data->getId();
        $profile->summary = $data->getAboutMe();
        $profile->firstName = $data->getName()->getGivenName();
        $profile->lastName = $data->getName()->getFamilyName();
        $profile->birthday = $data->getBirthday();
        $profile->pictureUrl = $data->getImage()->getUrl();
        $profile->profileUrl = $data->getUrl();
        $profile->gender = $data->getGender();
        $profile->ageRange = [
            'min' => $data->getAgeRange()->getMin(),
            'max' => $data->getAgeRange()->getMax()
        ];
        $profile->location = $data->getCurrentLocation();
        $profile->emails = $data->getEmails();
        $profile->occupation = $data->getOccupation();
        $profile->relationshipStatus = $data->getRelationshipStatus();
        
        foreach ($data->getOrganizations() as $org) {
            $profile->organizations[] = [
                'name' => $org->getName(),
                'title' => $org->getTitle(),
                'type' => $org->getType(),
                'location' => $org->getLocation(),
                'department' => $org->getDepartment()
            ];
        }

        return $profile;
    }
}
