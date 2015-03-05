<?php

// https://developer.linkedin.com/forum/oauth-access-token-expiry
// https://developers.facebook.com/blog/post/2011/05/13/how-to--handle-expired-access-tokens/
// https://developers.facebook.com/docs/roadmap/completed-changes/offline-access-removal
// https://dev.twitter.com/oauth/overview/faq

namespace Access2Me\Service;

use Facebook\FacebookSession;
use Facebook\FacebookRequestException;

use Access2Me\Helper;


class TokenRefresher
{
    protected $appConfig;

    public function __construct($appConfig)
    {
        $this->appConfig = $appConfig;
    }

    public function isDueToExpire($createdAt, $expiresAt)
    {
        // assume token is valid if it is not expired
        if ($expiresAt == null) {
            return false;
        }

        $createdAt = $createdAt->getTimestamp();
        $expiresAt = $expiresAt->getTimestamp();

        $lifetime = intval(($expiresAt - $createdAt) * 0.8); // 80% of lifetime passed

        return ($createdAt + $lifetime) < (new \DateTime)->getTimestamp();
    }

    /**
     * Extends lifetime without call external service
     * Suitable for newly created tokens
     *
     * @param $serviceId
     * @param $token
     * @return array
     */
    public function extendExpireTime($serviceId, $token)
    {
        $now = new \DateTimeImmutable();
        if ($serviceId == Service::LINKEDIN) {
            $expiresAt = $now->add(new \DateInterval('P60D'));      // from the doc
        } elseif ($serviceId == Service::FACEBOOK) {
            $expiresAt = $token->getExpiresAt();
        } elseif ($serviceId == Service::TWITTER) {
            $expiresAt = $now->add(new \DateInterval('P30D'));      // twitter token doesn't have expiration time, assume 30 days
        }

        return [
            'created_at' => $now,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Extends lifetime by calling external service
     * Suitable for refreshing already obtained tokens
     *
     * @param \Access2Me\Model\Sender $sender
     * @return bool
     * @todo Currently we just check if token is valid
     *       If token is invalid we can try to refresh it (get another access token)
     * @todo update access token
     */
    public function extendLifetime($serviceId, $token)
    {
        if ($serviceId == Service::LINKEDIN) {
            try {
                $linkedin = new Helper\Linkedin($this->appConfig['services']['linkedin']);
                $linkedin->getProfile($token);
                $time = $this->extendExpireTime($serviceId, null);
            } catch (Helper\LinkedinException $ex) {
                return false;
            }
        }
        elseif ($serviceId == Service::FACEBOOK) {
            try {
                FacebookSession::setDefaultApplication(
                    $this->appConfig['services']['facebook']['appId'],
                    $this->appConfig['services']['facebook']['appSecret']
                );
                $facebook = new Helper\Facebook($token);
                $facebook->validate();
                $token = $facebook->getSession()->getAccessToken();
                $time = $this->extendExpireTime($serviceId, $token);
            } catch (FacebookRequestException $ex) {
                return false;
            }
        }
        elseif ($serviceId == Service::TWITTER) {
            try {
                // to validate we simply request user profile
                $twitter = new Helper\Twitter($this->appConfig['services']['twitter']);
                $twitter->getUserRepresentation($token);
                $time = $this->extendExpireTime($serviceId, null);
            } catch (Helper\TwitterException $ex) {
                // can't fetch profile
                return false;
            }
        } else {
            throw new \InvalidArgumentException('Unsupported service');
        }
        
        return [
            'token' => $token,
            'time' => $time
        ];
    }
}
