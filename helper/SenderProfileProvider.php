<?php

namespace Access2Me\Helper;

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Access2Me\Model;
use Logging;

class SenderProfileProvider
{
    private $servicesConfig;
    
    public function __construct(array $servicesConfig)
    {
        $this->servicesConfig = $servicesConfig;
    }

    /**
     * Checks that profile data is recent
     * 
     * @param \Access2Me\Model\Sender $sender
     * @return boolean
     */
    public function isRecent($sender)
    {
        if ($sender->getProfile() == null) {
            return false;
        }

        // two weekes
        $interval = new \DateInterval('P2W');
        $isRecent = $sender->getProfileDate() > (new \DateTime())->sub($interval);
            
        return $isRecent;
    }

    /**
     * Returns senders profile for every available service
     * using cached version if it is available and recent
     * 
     * @param \Access2Me\Model\Sender[] $senders
     * @return array Profile 
     */
    public function getProfile($senders)
    {
        if (empty($senders)) {
            return null;
        }

        $profile = array(
            'sender' => $senders[0]->getSender(),
            'services' => array()
        );

        foreach ($senders as $sender) {
            $prof = null;

            // do we have cached profile ?
            $haveCached = $sender->getProfile() !== null;
            
            // do we have recent cached profile ?
            if ($haveCached && $this->isRecent($sender)) {
                $prof = array(
                    'cached' => true,
                    'recent' => true,
                    'profile' => $sender->getProfile()
                );
            } else {
                // we don't have cached profile or it is old
                $prof = $this->fetchProfile($sender);
                
                // new profile fecthed
                if ($prof !== false) {
                    $prof = array(
                        'cached' => false,
                        'recent' => true,
                        'profile' => $prof
                    );
                } else {
                    // can't fetch profile, try to use old
                    if ($haveCached) {
                        $prof = array(
                            'cached' => true,
                            'recent' => false,
                            'profile' => $sender->getProfile()
                        );
                    }
                }
            }
            
            $profile['services'][$sender->getService()] = $prof;
        }

        return $profile;
    }

    /**
     * Fetches profile using specified service
     * 
     * @param \Access2Me\Model\Sender $sender
     * @return array
     * @throws Exception
     */
    protected function fetchProfile(Model\Sender $sender)
    {
        switch ($sender->getService()) {
            case Model\SenderRepository::SERVICE_LINKEDIN:
                return $this->fetchLinkedinProfile(
                    $sender,
                    Model\SenderRepository::SERVICE_LINKEDIN
                );

            case Model\SenderRepository::SERVICE_FACEBOOK:
                return $this->fetchFacebookProfile(
                    $sender,
                    $this->servicesConfig[Model\SenderRepository::SERVICE_FACEBOOK]
                );

            case Model\SenderRepository::SERVICE_TWITTER:
                return $this->fetchTwitterProfile(
                    $sender,
                    Model\SenderRepository::SERVICE_TWITTER
                );

            default:
                throw new Exception('Unknown service ' . $sender->getService());
        }
    }

    
    public function fetchFacebookProfile($sender, $serviceConfig)
    {
        try {
            // initialize facebook session
            FacebookSession::setDefaultApplication(
                $serviceConfig['appId'],
                $serviceConfig['appSecret']
            );
            $facebook = new Facebook($sender->getOAuthKey());

            // validate session
            $facebook->validate();

            // get sender profile
            $profile = $facebook->getProfile();

            return $profile;

        } catch (FacebookRequestException $ex) {
            Logging::getLogger()->error(
                $ex->getMessage(),
                array('exception' => $ex)
            );
            return false;
        }
    }
}
