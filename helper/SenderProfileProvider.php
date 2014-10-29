<?php

namespace Access2Me\Helper;

use Access2Me\Model;

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
     * @param boolean $useCached
     * @return array Profile 
     *      array(
     *          sender => \Access2Me\Model\Sender(),
     *          services => array(
     *              array(serviceId => array(
     *                  'cached' => boolean,
     *                  'recent' => boolean,
     *                  'profile' => Access2Me\ProfileProvider\Profile
     *              )
     *          )
     *      )
     */
    public function getProfiles($senders, $useCached = true)
    {
        if (empty($senders)) {
            return null;
        }

        if (!is_array($senders)) {
            $senders = array($senders);
        }

        $profile = array(
            'sender' => $senders[0]->getSender(),
            'services' => array()
        );

        foreach ($senders as $sender) {
            $prof = null;

            // do we have cached profile ?
            $haveCached = $sender->getProfile() !== null && $useCached;
            
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

    /**
     * Save profiles for caching purpose
     * Doesn't commit changes
     * 
     * @param \Access2Me\Model\Sender[] $senders
     * @param array $profiles
     */
    public function storeProfiles($senders, $profiles)
    {
        if ($profiles) {
            $map = array();
            foreach ($senders as $sender) {
                $map[$sender->getService()] = $sender;
            }

            foreach ($profiles['services'] as $id => $service) {
                if ($service !== null
                    && $service['cached'] == false
                ) {
                    $map[$id]->setProfile($service['profile']);
                    $map[$id]->setProfileDate(new \DateTime());
                }
            }
        }
    }

    /**
     * 
     * @param array $profiles profiles returned by getProfiles
     * @param int $serviceId
     */
    public function getProfileByServiceId($profiles, $serviceId)
    {
        if (isset($profiles['services'][$serviceId]['profile'])) {
            return $profiles['services'][$serviceId]['profile'];
        }
        
        return null;
    }

    public function getCombiner($profiles)
    {
        if (!$profiles || !isset($profiles['services'])) {
            throw new \InvalidArgumentException('profiles');
        }

        $data = array();
        foreach ($profiles['services'] as $serviceId=>$item) {
            if ($item) {
                $data[$serviceId] = $item['profile'];
            }
        }

        return new ProfileCombiner($data);
    }
}
