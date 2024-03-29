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

    public static function splitEmail($email)
    {
        $pos = strrpos($email, '@');
        if ($pos === false) {
            return false;
        }

        $result['mailbox'] = substr($email, 0, $pos);
        $result['domain'] = substr($email, $pos+1);

        if (empty($result['mailbox']) || empty($result['domain'])) {
            return false;
        }

        return $result;
    }

    /**
     * Correct parsing of "Received" header is very complex (rfc5321, rfc5322)
     * 
     * @param array $headers
     * @return array 
     */
    public static function parseReceivedHeaders($headers)
    {
       $pattern = '/for (<.*?>|.*?)(;|\s|$)/';
       $result = [];

       foreach ($headers as $header) {
           $ret = preg_match($pattern, $header, $matches);
           if ($ret === false || $ret === 0) {
               continue;
           }

           $result[] = [
               'for' => \ezcMailTools::parseEmailAddress($matches[1])
           ];
       }

       return $result;
    }

    /**
     * Parses and converts text date into uniform representation with UTC tz
     * 
     * @param string $date
     * @return string
     */
    public static function parseDate($date)
    {
        try {
            $dt = new \DateTime($date);
            // convert date to UTC tz
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $ex) {
            return null;
        }
    }

    /**
     * Converts parsed email to the form to be stored in the database
     *
     * @param array $message
     */
    public static function toDatabaseRecord($message)
    {
        $mail = $message['mail'];
        $record = array();

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
        $record['created_at'] = self::parseDate($mail->getHeader('Date'));

        $replyTo = \ezcMailTools::parseEmailAddress($mail->getHeader('Reply-To'));
        
        // parse headers to find Return-Path or From for reply_email
        $record['reply_email'] = isset($replyTo)
            ? $replyTo->email : $mail->from->email;
        
        return $record;
    }

    /**
     * Collects recipients from trace information.
     * Mail can be forwarded so there can be more than one recipient.
     * Fallback value is address from the "To" header.
     * 
     * @param \ezcMail $mail
     * @return array recipients from the last to first
     */
    public static function getTracedRecipients(\ezcMail $mail)
    {
        $headers = $mail->getHeader('Received', true);
        $parsed = self::parseReceivedHeaders($headers);
        // fallback address
        $parsed[] = [
            'for' => \ezcMailTools::parseEmailAddress($mail->to[0]->email)
        ];

        $recipients = [];
        foreach ($parsed as $received) {
            if (isset($received['for'])) {
                $recipients[] = $received['for']->email;
            }
        }

        return $recipients;
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
     * Builds new message ready to be send to user
     *
     * @param array $to user entity
     * @param array $message message entity
     * @param string|null $header
     * @param array $options options to customize email (from_name)
     * @return \ezcMail
     */
    public static function buildMessage($to, $message, $header = null, $options = [])
    {
        // get message body of the original message
        $body = self::getMessageBody(
            $message['header'] . "\r\n\r\n" . $message['body']
        );

        // prepend header
        if ($header !== null) {
        	$body = new \ezcMailMultipartMixed($header, $body);
        }

        // build new message
        $fromName = isset($options['from_name']) ? $options['from_name']: $message['from_name'];

        $newMail = new \ezcMail();
        $newMail->from = new \ezcMailAddress('noreply@access2.me', $fromName);
        $newMail->to = array(new \ezcMailAddress($to['mailbox']));
        $newMail->setHeader('Reply-To', $message['reply_email']);
        $newMail->setHeader('X-Mailer', '');
        $newMail->subject = $message['subject'];
        $newMail->body = $body;

        // do not include User-Agent header in the mail
        $newMail->appendExcludeHeaders(array('User-Agent'));
        
        return $newMail;
    }

    public static function buildAlternativeMessage($text, $alternative)
    {
        $altBody = new \ezcMailText($alternative);
        $body = new \ezcMailText($text);
        $body->subType = 'html';

        $message = new \ezcMailMultipartAlternative($altBody, $body);
        return $message;
    }
}
