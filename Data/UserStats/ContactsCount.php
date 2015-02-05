<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;
use Access2Me\Model\SenderRepository;

class ContactsCount implements ResourceInterface
{
    /**
     * @var SenderRepository
     */
    private $senderRepo;

    public function __construct(SenderRepository $senderRepo)
    {
        $this->senderRepo = $senderRepo;
    }

    public function getType()
    {
        return UserStats::CONTACTS_COUNT;
    }

    public function get($userId)
    {
        return $this->senderRepo->getAuthenticatedCountByUser($userId);
    }
}
