<?php

namespace Access2Me\Message;

use Access2Me\Helper;
use Access2Me\Model;


/**
 * Guesses the owner of the message based on message headers
 */
class OwnerGuesser
{
    /**
     * @var Model\UserRepository
     */
    protected $userRepo;

    /**
     * @var Model\UserEmailRepository
     */
    protected $userEmailRepo;

    public function __construct(Model\UserRepository $userRepo, Model\UserEmailRepository $userEmailRepo)
    {
        $this->userRepo = $userRepo;
        $this->userEmailRepo = $userEmailRepo;
    }

    /**
     * Searches for the first entry in $emails
     * that matches one of the user mailboxes
     * 
     * @param array $emails
     * @param array $users
     * @return array|null user entity
     */
    protected function findFirstUser($emails, $users)
    {
        // map of users to their mailboxes
        $m2u = [];
        foreach ($users as $user) {
            $mailbox = strtolower($user['mailbox']);
            $m2u[$mailbox] = $user;
        }

        // find first recipient that is our user
        $user = null;
        foreach ($emails as $email) {
            if (isset($m2u[$email])) {
                $user = $m2u[$email];
                break;
            }
        }

        return $user;
    }

    /**
     * Get all recipients from To and Cc headers
     * 
     * @param \ezcMail $mail
     * @return array
     */
    protected function getEmailRecipients(\ezcMail $mail)
    {
        $recipients = [];
        foreach ($mail->to as $address) {
            $recipients[] = strtolower($address->email);
        }

        foreach ($mail->cc as $address) {
            $recipients[] = strtolower($address->email);
        }

        return $recipients;
    }

    /**
     * Tries to find the owner of the email among email recipients (Received header)
     * 
     * @param \ezcMail $mail
     * @param \Access2Me\Model\UserRepository $usersRepo
     * @param \Access2Me\Model\UserEmailRepository $userEmailsRepo
     * @return array|null user entity
     */
    public function getMessageOwner(\ezcMail $mail)
    {
        // get recipients from Received headers
        $emails = Helper\Email::getTracedRecipients($mail);

        // transform all emails to lowercase
        foreach ($emails as &$email) {
            $email = strtolower($email);
        }

        // find user to whom this email is originated
        // (mailbox address that appears first in the list)
        $unique = array_unique($emails);
        $users = $this->userRepo->findAllByMailboxes($unique);
        $user = $this->findFirstUser($emails, $users);

        // we have user
        // now find if he allowed messages to one
        // of the dest addresses (to, cc) to be accepted for his mailbox
        if ($user) {
            $recipients = $this->getEmailRecipients($mail);
            $userEmails = array_map(
                function (Model\UserEmail $ue) {
                    return $ue->getEmail();
                },
                $this->userEmailRepo->findByUserId($user['id'])
            );

            // FIXME: fallback address. Do we need it ?
            $userEmails[] = $user['mailbox'];
            
            $result = array_intersect($recipients, $userEmails);

            // user allowed messages to be accepted for some of the dest addresses
            if (count($result > 0)) {
                return $user;
            }
        }

        return null;
    }
}
