<?php

namespace Access2Me\Helper;

use Access2Me\Model;
use Logging;

class SenderProfileProvider
{
    /**
     * @var \Access2Me\ProfileProvider\ProfileProviderInterface[] 
     */
    private $providers;
    
    public function __construct($providers = array())
    {
        $this->providers = $providers;
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
        $serviceId = $sender->getService();

        if (!isset($this->providers[$serviceId])) {
            throw new \Exception('Unknown service ' . $serviceId);
        }

        return $this->providers[$serviceId]->fetchProfile($sender);
    }
}
