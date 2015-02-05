<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;
use Access2Me\Model\MessageRepository;

class MessagesCount implements ResourceInterface
{
    /**
     * @var MessageRepository
     */
    private $mesgRepo;

    public function __construct(MessageRepository $mesgRepo)
    {
        $this->mesgRepo = $mesgRepo;
    }

    public function getType()
    {
        return UserStats::MESSAGES_COUNT;
    }

    public function get($userId)
    {
        return $this->mesgRepo->getCountByUser($userId);
    }
}
