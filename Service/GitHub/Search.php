<?php

namespace Access2Me\Service\GitHub;

/**
 * Search GitHub
 * Extension for \Github\Api
 *
 * Example:
 * $client = new \Github\Client(
 *      new \Github\HttpClient\CachedHttpClient(
 *          ['cache_dir' => $appConfig['cache_dir'] . '/github-api-cache'];
 *      )
 * );
 * $search = new Search($client);
 * $users = $search->users(['tom', 'in:email', 'followers:>100'])
 *
 * @link https://developer.github.com/v3/search/
 * @package Access2Me\Service\GitHub
 */
class Search extends \Github\Api\AbstractApi
{
    const SORT_FIELD_BEAST_MATCH = null;
    const SORT_FIELD_FOLLOWERS = 'followers';
    const SORT_FIELD_REPOSITORIES = 'repositories';
    const SORT_FIELD_JOINED = 'joined';     // joined datetime

    const SORT_ORDER_ASC = 'asc';
    const SORT_ORDER_DESC = 'desc';

    /**
     * Search users
     *
     * $search->users(['some name', 'in:email', 'followers:>100'])
     *
     * @param array $query array of search qualifier:value delimited with a colon
     * @param null $sortField
     * @param string $sortOrder
     * @return \Guzzle\Http\EntityBodyInterface|mixed|string
     * @link https://developer.github.com/v3/search/#search-users
     */
    public function users($query, $sortField = self::SORT_FIELD_BEAST_MATCH, $sortOrder = self::SORT_ORDER_ASC)
    {
        $query = implode(' ', $query);
        $params = [
            'q' => $query,
            'sort' => $sortField,
            'order' => $sortOrder
        ];

        return $this->get('search/users', $params);
    }
}
