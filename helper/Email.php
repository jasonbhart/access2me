<?php

namespace Access2Me\Helper;

class Email
{

    /**
     * Check if email is valid
     * Very simple validation.
     *
     * @param string $email
     * @return bool
     */
    public static function isAddressValid($email)
    {
        return preg_match('/[^@]+@[^@]+/', $email);
    }

    /**
     * Parses address string into array of addresses
     *
     * @param string $data
     */
    public static function parseAddressList($data)
    {
        // we can have few addresses
        $records = explode(',', $data);
        $addresses = array();

        foreach ($records as $record) {
            $args = explode('<', $record, 2);

            // do we have just address ?
            if (count($args) < 2) {
                if (self::isAddressValid($args[0])) {
                    $addresses[] = array('email' => $args[0]);
                }
            } else {
                // we have something like: Name <address>

                $name = trim($args[0]);
                $email = trim(rtrim($args[1], '>'));

                // address is defined ?
                if ($email) {
                    $address = array('email' => $email);
                    if ($name) {
                        $address['name'] = $name;
                    }
                    $addresses[] = $address;
                }
            }
        }

        return $addresses;
    }

    /**
     * Parse headers from a string
     *
     * @param string $data raw email headers
     * @return array an associative array
     */
    public static function parseHeaders($data)
    {
        $lines = explode("\r\n", $data);
        $records = array();

        foreach ($lines as $line) {
            // line that begins with a whitespace is continuation of the current header
            $continuation = preg_match('/^\s+/', $line);

            $line = trim($line);

            if (!$line)
                continue;

            if ($continuation) {
                if ($records) {
                    $records[count($records) - 1] .= ' ' .$line;
                }
            }
            else {
                $records[] = $line;
            }
        }

        // this headers will be parse specially
        $addressHeaders = array(
            'to', 'from', 'cc', 'bcc', 'reply-to', 'sender', 'return-path'
        );

        // parse records to get headers
        $headers = array();
        foreach ($records as $record) {
            $args = explode(':', $record, 2);
            if (count($args) != 2)
                continue;

            $name = trim(strtolower($args[0]));
            $data = trim($args[1]);

            // some records contain addresses
            if (in_array($name, $addressHeaders)) {
                $headers[$name] = self::parseAddressList($data);
            }
            else {
                $headers[$name] = $data;
            }
        }

        return $headers;
    }

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
}
