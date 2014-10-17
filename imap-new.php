<?php

class IMAP_New
{
    protected $host = null;
    protected $user = null;
    protected $pass = null;
    protected $port = 993;

    protected $protocol       = "imap";
    protected $crypto         = "ssl";
    protected $certificate    = "novalidate-cert";
    protected $folderLocation = null;

    public $errors      = array();
    public $lastMessage = null;

    public $connection = null;

    public function __construct($parameters)
    {
        if (empty($parameters)) {

            $this->lastMessage = 'Required paramters missing';
            $this->errors['missing_parameters'] = true;

            return false;
        }

        $this->host = $parameters['host'];
        $this->user = $parameters['user'];
        $this->pass = $parameters['pass'];

        return true;
    }
    //--------------------------------------------------------------------------


    public function connect()
    {
        $mailbox =  "{" . $this->host . ":" . $this->port . "/" . $this->protocol . "/" . $this->crypto . "/" . $this->certificate . "}";

        try {
            $this->connection = imap_open($mailbox, $this->user, $this->pass);
        } catch (Exception $e) {
            $this->lastMessage = 'Unable to connect: ' . $e;
            $this->errors['connection'] = true;

            return false;
        }

        $this->lastMessage = 'Connection established';
        return true;
    }
    //--------------------------------------------------------------------------


    public function getMessageList($onlyNewMessages = true)
    {
    }
    //--------------------------------------------------------------------------


    public function getMessageCount($onlyNewMessages = true)
    {
    }
    //--------------------------------------------------------------------------


    public function getMessageData($messageId = null)
    {
        if (empty($messageId)) {
            $this->lastMessage = 'Missing messageId in getMessageDataById';
            $this->errors['syntax'] = true;

            return false;
        }

        $messageData = array(
            'overview'     => $this->getMessageOverview($messageId),
            'header'       => $this->getMessageHeader($messageId),
            'headerDetail' => $this->getMessageRawHeader($messageId),
            'body'         => $this->getMessageBody($messageId),
            'structure'    => $this->getMessageStructure($messageId)
        );


        if (!empty($messageData)) {
            return $messageData;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    public function getMessageOverview($messageId)
    {
        $overview = imap_fetch_overview($this->connection, $messageId, 0);

        if (!empty($overview)) {
            return $overview;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    public function getMessageHeader($messageId)
    {
        $header = imap_headerinfo($this->connection, $messageId);

        if (!empty($header)) {
            return $header;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    public function getMessageRawHeader($messageId)
    {
        $rawHeader = imap_fetchheader($this->connection, $messageId);

        if (!empty($rawHeader)) {
            return $rawHeader;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    public function getMessageBody($messageId)
    {
        $body = trim(utf8_decode(quoted_printable_decode(imap_fetchbody($this->connection, $messageId, 2.1))));

        if (empty($body)) {
            $body = trim(utf8_decode(quoted_printable_decode(imap_fetchbody($this->connection, $messageId, 2))));
        }

        if (empty($body)) {
            $body = trim(utf8_decode(quoted_printable_decode(imap_fetchbody($this->connection, $messageId, 1))));
        }

        if (!empty($body)) {
            return $body;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    public function getMessageStructure($messageId)
    {
        $structure = imap_fetchstructure($this->connection , $messageId);

        if (!empty($structure)) {
            return $structure;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    public function getMessageSubject($messageId)
    {
        $overview = $this->getMessageOverview($messageId);

        if (!empty($overview[0]->subject)) {
            return (string) $overview[0]->subject;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    public function getMessageTo($messageId)
    {
        $overview = $this->getMessageOverview($messageId);

        if (!empty($overview[0]->to)) {
            return (string) $overview[0]->to;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    public function getMessageFrom($messageId)
    {
        $overview = $this->getMessageOverview($messageId);

        if (!empty($overview[0]->from)) {
            return (string) $overview[0]->from;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    public function moveMessage($uid, $imap, $mailbox) {
        try {
            imap_mail_move($imap, $uid, $mailbox, CP_UID);

            return true;
        } catch (Exception $e) {

            return false;
        }
    }
    //--------------------------------------------------------------------------


    public function createFolder($imap, $folder) {
        try {
            imap_createmailbox($imap, $folder);

            return true;
        } catch (Exception $e) {
            $this->lastMessage = 'Unable to create folder: ' . $e;

            return false;
        }
    }
    //--------------------------------------------------------------------------


    public function getBodyNew($uid, $imap) {
        $body = $this->get_part($imap, $uid, "TEXT/HTML");
        // if HTML body is empty, try getting text body
        if ($body == "") {
            $body = $this->get_part($imap, $uid, "TEXT/PLAIN");
        }
        return $body;
    }
    //--------------------------------------------------------------------------


    public function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false) {
        if (!$structure) {
               $structure = imap_fetchstructure($imap, $uid, FT_UID);
        }

        if ($structure) {
            if ($mimetype == $this->get_mime_type($structure)) {
                if (!$partNumber) {
                    $partNumber = 1;
                }
                $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
                switch ($structure->encoding) {
                    //0	7BIT
                    //1	8BIT
                    //2	BINARY
                    //3	BASE64
                    //4	QUOTED-PRINTABLE
                    //5	OTHER
                    case 3: return imap_base64($text);
                    case 4: return imap_qprint($text);
                    default: return $text;
               }
           }

            // multipart
            if ($structure->type == 1) {
                foreach ($structure->parts as $index => $subStruct) {
                    $prefix = "";
                    if ($partNumber) {
                        $prefix = $partNumber . ".";
                    }
                    $data = $this->get_part($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
                    if ($data) {
                        return $data;
                    }
                }
            }
        }
        return false;
    }
    //--------------------------------------------------------------------------


    public function get_mime_type($structure) {
        $primaryMimetype = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");

        if ($structure->subtype) {
            return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
        }
        return "TEXT/PLAIN";
    }
    //--------------------------------------------------------------------------


    public function decode7Bit($text) {
        // If there are no spaces on the first line, assume that the body is
        // actually base64-encoded, and decode it.
        $lines = explode("\r\n", $text);
        $first_line_words = explode(' ', $lines[0]);
        if ($first_line_words[0] == $lines[0]) {
            $text = base64_decode($text);
        }

        // Manually convert common encoded characters into their UTF-8 equivalents.
        $characters = array(
            '=20' => ' ', // space.
            '=E2=80=99' => "'", // single quote.
            '=0A' => "\r\n", // line break.
            '=A0' => ' ', // non-breaking space.
            '=C2=A0' => ' ', // non-breaking space.
            "=\r\n" => '', // joined line.
            '=E2=80=A6' => '…', // ellipsis.
            '=E2=80=A2' => '•', // bullet.
        );

        // Loop through the encoded characters and replace any that are found.
        foreach ($characters as $key => $value) {
            $text = str_replace($key, $value, $text);
        }

        return $text;
    }
    //--------------------------------------------------------------------------
}
