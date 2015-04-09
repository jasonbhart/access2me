<?php

namespace Access2Me\Message;

use Access2Me\Helper;
use Access2Me\Model;

/**
 * Determines if message passes defined rules and saves it to the storage
 * for further processing
 * Useful for saving messages received from IMAP etc.
 */
class Saver
{
    /**
     * @var Model\MessageRepository
     */
    protected $mesgRepo;
    
    /**
     * @var MessageOwnerGuesser
     */
    protected $messageOwnerGuesser;

    public function __construct(Model\MessageRepository $mesgRepo, OwnerGuesser $messageOwnerGuesser)
    {
        $this->mesgRepo = $mesgRepo;
        $this->messageOwnerGuesser = $messageOwnerGuesser;
    }
    
    /**
     * Converts parsed email to the message entity
     *
     * @param array $message
     * @return array message entity
     */
    protected function toDatabaseRecord($message)
    {
        $mail = $message['mail'];
        $record = [];

        $record['message_id'] = $mail->messageId;
        $record['from_name'] = $mail->from->name;
        $record['from_email'] = $mail->from->email;
        $record['to_email'] = $mail->to[0]->email;
        $record['subject']   = $mail->subject;
        $record['header']    = $message['raw_header'];
        $record['body']      = $message['raw_body'];
        $record['status']    = 0;
        $record['appended_to_unverified'] = 0;
        
        // parse Date header
        $record['created_at'] = Helper\Email::parseDate($mail->getHeader('Date'));

        $replyTo = \ezcMailTools::parseEmailAddress($mail->getHeader('Reply-To'));
        
        // parse headers to find Return-Path or From for reply_email
        $record['reply_email'] = isset($replyTo)
            ? $replyTo->email : $mail->from->email;
        
        return $record;
    }

    public function save($message)
    {
        // filter out not suitable messages
        if (!Helper\Email::isSuitable($message)) {
            return false;
        }

        $user = $this->messageOwnerGuesser->getMessageOwner($message['mail']);
        $record = $this->toDatabaseRecord($message);

        // do not have owner
        if ($user === null) {
            $msg = sprintf(
                'Can\'t find message owner: (%s) -> (%s)',
                $record['from_email'],
                $record['to_email']
            );
            \Logging::getLogger()->addInfo($msg);
            return false;
        }

        $record['user_id'] = $user['id'];

        $this->mesgRepo->insert($record);
        
        return true;
    }
}
