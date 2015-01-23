<?php

// https://developer.linkedin.com/forum/oauth-access-token-expiry
// https://developers.facebook.com/blog/post/2011/05/13/how-to--handle-expired-access-tokens/
// https://developers.facebook.com/docs/roadmap/completed-changes/offline-access-removal
// https://dev.twitter.com/oauth/overview/faq

namespace Access2Me\Service;

use Facebook\FacebookSession;
use Facebook\FacebookRequestException;

use Access2Me\Helper;
use Access2Me\Model;


class TokenRefresher
{
    protected $appConfig;

    public function __construct($appConfig)
    {
        $this->appConfig = $appConfig;
    }

    public function isDueToExpire(Model\Sender $sender)
    {
        // assume token is valid if it is not expired
        if ($sender->getExpiresAt() == null) {
            return false;
        }

        $createdAt = $sender->getCreatedAt()->getTimestamp();
        $expiresAt = $sender->getExpiresAt()->getTimestamp();

        $lifetime = intval(($expiresAt - $createdAt) * 0.8); // 80% of lifetime passed

        return ($createdAt + $lifetime) < (new \DateTime)->getTimestamp();
    }

    public function extendExpireTime(Model\Sender $sender, $token)
    {
        $now = new \DateTimeImmutable();
        if ($sender->getService() == Service::LINKEDIN) {
            $expiresAt = $now->add(new \DateInterval('P60D'));      // from the doc
        } elseif ($sender->getService() == Service::FACEBOOK) {
            $expiresAt = $token->getExpiresAt();
        } elseif ($sender->getService() == Service::TWITTER) {
            $expiresAt = $now->add(new \DateInterval('P30D'));      // twitter token doesn't have expiration time, assume 30 days
        }

        $sender->setCreatedAt($now);
        $sender->setExpiresAt($expiresAt);
    }

    /**
     * @param \Access2Me\Model\Sender $sender
     * @return bool
     * @todo Currently we just check if token is valid
     *       If token is invalid we can try to refresh it (get another access token)
     * @todo update access token
     */
    public function extendLifetime(\Access2Me\Model\Sender $sender)
    {
        $service = $sender->getService();
        if ($service == Service::LINKEDIN) {
            try {
                $linkedin = new Helper\Linkedin($this->appConfig['services']['linkedin']);
                $linkedin->getProfile($sender->getOAuthKey());
                $this->extendExpireTime($sender, null);
            } catch (Helper\LinkedinException $ex) {
                return false;
            }
        }
        elseif ($service == Service::FACEBOOK) {
            try {
                FacebookSession::setDefaultApplication(
                    $this->appConfig['services']['facebook']['appId'],
                    $this->appConfig['services']['facebook']['appSecret']
                );
                $facebook = new Helper\Facebook($sender->getOAuthKey());
                $facebook->validate();
                $token = $facebook->getSession()->getAccessToken();
                $this->extendExpireTime($sender, $token);
            } catch (FacebookRequestException $ex) {
                return false;
            }
        }
        elseif ($service == Service::TWITTER) {
            try {
                // to validate we simply request user profile
                $twitter = new Helper\Twitter($this->appConfig['services']['twitter']);
                $twitter->getUserRepresentation($sender->getOAuthKey());
                $this->extendExpireTime($sender, null);
            } catch (Helper\TwitterException $ex) {
                // can't fetch profile
                return false;
            }
        } else {
            throw new \InvalidArgumentException('Unsupported service');
        }
        
        return true;
    }
}
