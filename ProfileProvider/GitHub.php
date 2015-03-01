<?php

namespace Access2Me\ProfileProvider;

use Access2Me\Helper\DataConverter;
use Access2Me\Service;

/**
 * @todo add authentication to increase limits
 * @package Access2Me\ProfileProvider
 */
class GitHub implements ProfileProviderInterface
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
            $client = new \Github\Client(
                new \Github\HttpClient\HttpClient()
            );

            // search github user by email
            $search = new Service\GitHub\Search($client);
            $users = $search->users([
                $sender->getSender(),
                'in:email'
            ]);

            // not found
            if (!isset($users['items'][0]['login']))
                return null;

            // get user info
            $login = $users['items'][0]['login'];
            $user = $client->api('users')->show($login);

            return $this->convertToProfile($user);
        } catch (\Exception $ex) {
            throw new ProfileProviderException('Can\'t fetch github profile', 0, $ex);
        }
    }

    protected function convertToProfile($data)
    {
        $profile = new Profile\GitHub();
        $parseDate = function($value) {
            return new \DateTime($value);
        };

        $converter = new DataConverter([
            ['src' => 'id', 'conv' => 'intval'],
            'login',
            'name',
            'email',
            'company',
            'location',
            ['src' => 'hireable', 'conv' => 'boolval'],
            ['src' => 'bio', 'dst' => 'biography'],
            ['src' => 'site_admin', 'dst' => 'siteAdmin', 'conv' => 'boolval'],
            ['src' => 'followers', 'dst' => 'followersCount', 'conv' => 'intval'],
            ['src' => 'following', 'dst' => 'followingCount', 'conv' => 'intval'],
            ['src' => 'public_repos', 'dst' => 'publicReposCount', 'conv' => 'intval'],
            ['src' => 'url', 'dst' => 'apiUrl'],
            ['src' => 'avatar_url', 'dst' => 'avatarUrl'],
            ['src' => 'blog', 'dst' => 'blogUrl'],
            ['src' => 'repos_url', 'dst' => 'reposUrl'],
            ['src' => 'html_url', 'dst' => 'htmlUrl'],
            ['src' => 'created_at', 'dst' => 'createdAt', 'conv' => $parseDate],
            ['src' => 'updated_at', 'dst' => 'updatedAt', 'conv' => $parseDate],
        ]);

        return $converter->convert($data, $profile);
    }
}
