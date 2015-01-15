<?php

namespace Access2Me\Helper;

use \Access2Me\Model\UserSenderRepository;

class UserListProvider
{
    private $userId;

    /**
     * @var UserSenderRepository
     */
    private $repo;
    
    public function __construct($userId, UserSenderRepository $repo)
    {
        $this->userId = $userId;
        $this->repo = $repo;
    }

    public function search($email) {
        $parsed = Email::splitEmail($email);
        if ($parsed === false) {
            throw new \Exception('Invalid email address');
        }

        // search by full email address
        $result = $this->repo->getByUserAndSender($this->userId, $email);
        if ($result == null) {
            // didn't find matching email address, search by domain
            $result = $this->repo->getByUserAndSender($this->userId, $parsed['domain']);
        }

        if ($result == null) {
            return false;
        }

        return [
            'value' => $result['sender'],
            'type' => $result['type'],
            'access' => $result['access']
        ];
    }
}
