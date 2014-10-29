<?php

namespace Access2Me\Helper;

class Email
{
    /**
     * Checks that message fits the rules. (Suitable for processing)
     * Usually operates on data obtained from IMAP
     *
     * @param array $message array containing following keys:
     *   raw_header, raw_body, mail
     */
    public static function isSuitable($message)
    {
        $mail = $message['mail'];
        /*
         Check to see if from or return-path is NULL or:
            owner-*
            *-request
            MAILER-DAEMON
        */
        $from = isset($mail->from) ? $mail->from->email : null;
        $returnPath = isset($mail->returnPath) ? $mail->returnPath->email : null;

        $pattern = '/^MAILER-DAEMON|owner-.*|.*-request$/i';

        if ($from == NULL
            || preg_match($pattern, $from) != 0
            || $returnPath == null
            || preg_match($pattern, $returnPath) != 0
        ) {
            return false;
        }

        // do not respond to automatically submitted emails
        if (isset($mail->headers['auto-submitted']) && $mail->headers['auto-submitted'] != 'no') {
            return false;
        }

        // check if message contains Content-Type header
        if (empty($mail->headers['content-type'])) {
            $msg = sprintf(
                "Message(%s) doesn't contain Content-Type header",
                $mail->messageId
            );
            \Logging::getLogger()->info($msg);
            return false;
        }

        return true;
    }

    /**
     * Converts parsed email to the form to be stored in the database
     *
     * @param array $mail
     */
    public static function toDatabaseRecord($message)
    {
        $mail = $message['mail'];
        $record = array();

        $record['messageId'] = $mail->messageId;
        $record['subject']   = $mail->subject;
        $record['to']        = $mail->to[0]->email;
        $record['from']      = $mail->from->name;
        $record['fromEmail'] = $mail->from->email;
        $record['header']    = $message['raw_header'];
        $record['body']      = $message['raw_body'];

        // parse headers to find Return-Path or From for reply_email
        $record['replyEmail'] = isset($mail->returnPath)
            ? $mail->returnPath->email : $mail->from->email;
        
        return $record;
    }

    /**
     * Parses raw message and returns just message body with needed headers
     * that describe content like Content-Type, Content-Disposition,
     * Content-Transfer-Encoding, Content-ID
     * 
     * Use this method to build new email where you want to
     * include body of another email
     * 
     * @param string $message raw email message with headers
     * @return \ezcMailPart
     */
    public static function getMessageBody($rawMessage)
    {
        // parse raw message
        $message = new \ezcMailVariableSet($rawMessage);
        $parser = new \ezcMailParser();
        $mail = $parser->parseMail($message);

        // build body
        $body = $mail[0]->body;
        // if body is not multipart then copy global headers to it
        if (!($body instanceof \ezcMailMultipart)) {

            $headers = $mail[0]->headers;
            $required = array('Content-ID', 'Content-Type', 'Content-Disposition', 'Content-Transfer-Encoding');

            // copy headers
            foreach ($headers->getCaseSensitiveArray() as $header => $value) {
                if (in_array($header, $required)) {
                    $body->headers->offsetSet($header, $value);
                }
            }
        }
        
        return $body;
    }

    /**
     * Get content of access2.me info header
     * 
     * @param array $contact
     * @return \ezcMailMultipartAlternative
     */
    public static function getInfoHeader($profComb)
    {
        // data for template
        $contact = array(
            'picture_url' => $profComb->getFirst('pictureUrl'),
            'profile_urls' => $profComb->profileUrl,
            'email' => $profComb->getFirst('email'),
            'full_name' => $profComb->getFirst('fullName'),
            'headline' => $profComb->getFirst('headline'),
            'location' => $profComb->getFirst('location')
        );

        ob_start();
        include '../views/email_info_header.html';
        $infoText = ob_get_clean();
        
        // build our info header
        $altInfoBody = new \ezcMailText('This is the body in plain text for non-HTML mail clients');
        $infoBody = new \ezcMailText($infoText);
        $infoBody->subType = 'html';

        $info = new \ezcMailMultipartAlternative($altInfoBody, $infoBody);
        
        return $info;
    }

    /**
     * Builds new message ready to be send to user
     * by prepending info header to original message and filling in
     * all required info 
     * 
     * @param array $to user entity
     * @param \Access2Me\Helper\ProfileCombiner $fromContact contact build from profile
     * @param array $message message entity
     * @return \ezcMail
     */
    public static function buildVerifiedMessage($to, $profComb, $message)
    {
        // get message body of the original message
        $body = self::getMessageBody(
            $message['header'] . "\r\n" . $message['body']
        );

        // join our header and content of the original message
        $info = self::getInfoHeader($profComb);
        $newBody = new \ezcMailMultipartMixed($info, $body);

        // build new message
        $fromName = $profComb->getFirst('fullName');
 
        $newMail = new \ezcMail();
        $newMail->from = new \ezcMailAddress('noreply@access2.me', $fromName);
        $newMail->to = array(new \ezcMailAddress($to['email']));
        $newMail->setHeader('Reply-To', $message['reply_email']);
        $newMail->setHeader('X-Mailer', '');
        $newMail->subject = $message['subject'];
        $newMail->body = $newBody;

        // do not include User-Agent header in the mail
        $newMail->appendExcludeHeaders(array('User-Agent'));
        
        return $newMail;
    }
}
