<?php

class IMAP
{

    protected $host           = null;
    protected $user           = null;
    protected $port           = 993;
    protected $protocol       = "imap";
    protected $crypto         = "ssl";
    protected $certificate    = "novalidate-cert";
    protected $password       = null;
    protected $folderLocation = null;
    protected $lastMessage    = null;
    protected $errors         = array();

    public function __construct($params = array())
    {
        if (!empty($params)) {
            foreach ($params AS $prop => $val) {
                $this->{$prop} = $val;
            }
        }
        $required_params = $this->getInitParamStatus();

        if (!empty($required_params)) {
            $this->errors['required_params'] = $required_params;

            return false;
        }
    }

    //--------------------------------------------------------------------------

    protected function getInitParamStatus($tunnel = false)
    {
        $emptyParamsStr = "";
        $emptyParams    = array();
        $excludedParams = array("folderLocation", "lastMessage", "errors");

        foreach ($this as $key => $val) {
            if (empty($val) && !in_array($key, $excludedParams)) {
                $emptyParams[] = $key;
            }
        }

        if (!empty($emptyParams)) {
            $emptyParamsStr = implode(",", $emptyParams);
        }

        return $emptyParamsStr;
    }

    //--------------------------------------------------------------------------

    protected function connect($mailbox = null)
    {
        // default mailbox (Inbox)
        if (empty($mailbox)) {
            $mailbox = "{" . $this->host . ":" . $this->port . "/" . $this->protocol . "/" . $this->crypto . "/" . $this->certificate . "}";
        }

        if ($connection = imap_open($mailbox, $this->user, $this->password)) {
            $imap_obj          = imap_check($connection);
            $this->lastMessage = "connected to imap host " . $this->host . " (" . $imap_obj->Nmsgs . ")";
            return $connection;
        } else {
            $this->errors['connection_failed'] = "Can't connect to " . $this->host . " : " . imap_last_error();
            return false;
        }
    }

    //--------------------------------------------------------------------------

    public function checkInbox($filter = "UNSEEN")
    {
        $inbox = $this->connect(); // connect to imap server to get messages.

        if ($inbox === false) {
            // connection failed
            return false;
        }

        // get message count
        $countnum = imap_num_msg($inbox);

        if ($countnum > 0 && !empty($this->folderLocation)) {
            //move the email to our saved folder
            $imapresult = imap_mail_move($inbox, '1:' . $countnum, $this->folderLocation);
            if ($imapresult == false) {
                $this->errors['email_move_failed'] = "Can't move to " . $this->folderLocation . " : " . imap_last_error();
            }
            $this->connectionClose($inbox, CL_EXPUNGE); // connection close
        }

        $emails = imap_search($inbox, $filter);

        if (!empty($emails)) {
            return count($emails); // email(s) found
        } else {
            return 0;
        }
    }

    //--------------------------------------------------------------------------


    public function deleteMessage($uid) {
        try {
            $inbox = $this->connect();

            imap_delete($inbox, $uid, FT_UID);
            imap_expunge($inbox);

            return true;
        } catch (Exception $e) {

            return false;
        }
    }
    //--------------------------------------------------------------------------


    public function getInbox($filter = 'ALL')
    {
        $inbox = $this->connect(); // connect to imap server to get messages.
        $output = array();

        if (!empty($inbox)) {
            $emails = imap_search($inbox, $filter);

            /* if emails are returned, cycle through each... */
            if ($emails) {
                $output = $this->getEmailMessages($inbox, $emails);
            }
        }
        $this->connectionClose($inbox); // connection close

        return $output;
    }

    //--------------------------------------------------------------------------

    public function getInboxAttachments($filter = 'ALL')
    {
        $inbox = $this->connect(); // connect to imap server to get messages.
        $output = array();

        if (!empty($inbox)) {
            $emails = imap_search($inbox, $filter);

            /* if emails are returned, cycle through each... */
            if ($emails) {
                $output = $this->getEmailMessages($inbox, $emails);
            }
        }

        $this->connectionClose($inbox); // connection close

        return $output;
    }

    //--------------------------------------------------------------------------

    public function getSpam($filter='UNSEEN')
    {
        $spamMailBoxes = $this->getSpamMailBoxes();
        $output = array();

        // if spam mail box found
        if (!empty($spamMailBoxes)) {
            $spam = $this->connect($spamMailBoxes); // connect to imap server to get messages.

            if (!empty($spam)) {
                $emails = imap_search($spam, $filter);

                /* if emails are returned, cycle through each... */
                if ($emails) {
                    $output = $this->getEmailMessages($spam, $emails);
                }
            }
            $this->connectionClose($spam); // connection close
        }

        return $output;
    }

    //--------------------------------------------------------------------------

    protected function getSpamMailBoxes()
    {
        $spamMailBoxes = null;

        // open connection to get spam mail box
        $inbox = $this->connect(); // connect to imap server to get messages.

        $mailBoxes = imap_list($inbox, "{" . $this->host . ":" . $this->port . "/" . $this->protocol . "/" . $this->crypto . "/" . $this->certificate . "}", '*');

        foreach ($mailBoxes as $box) {
            if (strpos(strtolower($box), 'spam') !== false || strpos(strtolower($box), 'bulk') !== false) {
                $spamMailBoxes = $box;
                break;
            }
        }

        $this->connectionClose($inbox); // connection close

        return $spamMailBoxes;
    }

    //--------------------------------------------------------------------------

    protected function getEmailMessages($inbox, $emails)
    {
        /* begin output var */
        $output = array();
        /* put the newest emails on top */
        rsort($emails);

        /* for every email... */
        foreach ($emails as $email_number) {

            /* get information specific to this email */
            $overview     = imap_fetch_overview($inbox, $email_number, 0);
            $header       = imap_headerinfo($inbox, $email_number);
            $headerDetail = imap_fetchheader($inbox, $email_number);
            $structure    = imap_fetchstructure($inbox, $email_number);

            $body = trim(utf8_decode(quoted_printable_decode(imap_fetchbody($inbox, $email_number, 2.1))));

            if (empty($body)) {
                $body = trim(utf8_decode(quoted_printable_decode(imap_fetchbody($inbox, $email_number, 2))));
            }

            if (empty($body)) {
                $body = trim(utf8_decode(quoted_printable_decode(imap_fetchbody($inbox, $email_number, 1))));
            }

            $output[] = array(
                'overview'     => $overview,
                'header'       => $header,
                'headerDetail' => $headerDetail,
                'body'         => $body,
                'structure'    => $structure
            );
        }

        return $output;
    }

    //--------------------------------------------------------------------------

    protected function decodeImapText($var)
    {
        // decode utf-8 and iso-8859-1 encoded text
        if (ereg("=\?.{0,}\?[Bb]\?", $var)) {
            $var = split("=\?.{0,}\?[Bb]\?", $var);

            while (list($key, $value) = each($var)) {
                if (ereg("\?=", $value)) {
                    $arrTemp    = split("\?=", $value);
                    $arrTemp[0] = base64_decode($arrTemp[0]);
                    $var[$key]  = join("", $arrTemp);
                }
            }
            $var = join("", $var);
        }

        if (ereg("=\?.{0,}\?Q\?", $var)) {
            $var = quoted_printable_decode($var);
            $var = ereg_replace("=\?.{0,}\?[Qq]\?", "", $var);
            $var = ereg_replace("\?=", "", $var);
        }
        return trim($var);
    }

    //--------------------------------------------------------------------------

    protected function connectionClose($connection, $flag = 0)
    {
        imap_close($connection, $flag);
    }

    //--------------------------------------------------------------------------

    public function getLastMessage()
    {
        return $this->lastMessage;
    }

    //--------------------------------------------------------------------------

    public function getErrors()
    {
        return $this->errors;
    }

}
