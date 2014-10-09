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
     *   overview, header, headerDetail, body, structure
     */
    public static function isSuitable($message)
    {
        // parse headers
        $headers = self::parseHeaders($message['headerDetail']);

        /*
         Check to see if from or return-path is NULL or:
            owner-*
            *-request
            MAILER-DAEMON
        */
        $from = isset($headers['from']) && count($headers['from']) > 0
            ? $headers['from'][0]['email'] : null;
        $retPath = isset($headers['return-path']) && count($headers['return-path']) > 0
            ? $headers['return-path'][0]['email'] : null;

        $pattern = '/^MAILER-DAEMON|owner-.*|.*-request$/i';

        if ($from == NULL
            || preg_match($pattern, $from) != 0
            || $retPath == null
            || preg_match($pattern, $retPath) != 0
        ) {
            return false;
        }

        // do not respond to automatically submitted emails
        if (isset($headers['auto-submitted']) && $headers['auto-submitted'] != 'no') {
            return false;
        }

        // check if message contains Content-Type header
        if (empty($headers['content-type'])) {
            $msg = sprintf(
                "Message(%s) doesn't contain Content-Type header",
                $message['header']->message_id
            );
            \Logging::getLogger()->info($msg);
            return false;
        }

        return true;
    }

}
