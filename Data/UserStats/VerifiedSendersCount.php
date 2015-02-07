<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;
use Access2Me\Model\SenderRepository;

class VerifiedSendersCount implements ResourceInterface
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
        return UserStats::VERIFIED_SENDERS_COUNT;
    }

    public function get($user)
    {
        return $this->senderRepo->getAuthenticatedCountByUser($user['id']);
    }
}
