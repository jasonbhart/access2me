<?php

class SMTP
{
    private $reconnectTime = 5;

    protected $host          = null;
    protected $user          = null;
    protected $password      = null;
    protected $port          = 25;
    protected $timeout       = 15;
    protected $crypto        = null;
    protected $newline       = "\r\n";
    protected $myHost        = 'localhost';
    protected $socket        = null;
    protected $link          = null;
    protected $keys          = false;
    protected $lastError     = null;
    protected $lastStatus    = null;
    protected $errors        = array();
    protected $sshUser       = null;
    protected $sshPassword   = null;
    protected $sshPort       = 22;
    protected $sshTunnel     = false;
    protected $sshMethod     = 'ssh2';
    protected $sshPublicKey  = null;
    protected $sshPrivateKey = null;
    protected $sshHostKey    = 'ssh-rsa';
    protected $authenticated = false;

    public function __construct($params = array())
    {
        if (!empty($params)) {
            foreach ($params as $prop => $val) {
                $this->{$prop} = $val;
            }
        }

        if ($this->sshTunnel === true) {
            if (empty($this->sshMethod)) {
                $this->sshMethod = 'ssh2';
            }

            if (!extension_loaded($this->sshMethod)) {
                $this->errors['no_' . $this->sshMethod . '_ext'] = 'The ' . $this->sshMethod .
                    ' PHP extension is not available';

                return false;
            }

            if (!function_exists('stream_get_contents')) {
                $this->errors[$this->sshMethod . '_php_requirement'] = 'The ' . $this->sshMethod .
                    ' PHP extension is available, however, we require the PHP5 function' .
                    '<code>stream_get_contents()</code>';

                return false;
            }

            if (empty($this->sshPort)) {
                $this->sshPort = 22;
            }

            if (empty($this->sshHostKey)) {
                $this->sshHostKey = 'ssh-rsa';
            }
        }

        $required_params = $this->getInitParamStatus($this->sshTunnel);

        if (!empty($required_params)) {
            $this->errors['required_params'] = $required_params;

            return false;
        }

        return true;
    }
    //--------------------------------------------------------------------------


    protected function getInitParamStatus($tunnel = false)
    {
        $emptyParamsStr = "";
        $emptyParams    = array();

        if (!$tunnel) {
            $excludedParams = array(
                'crypto',
                'socket',
                'link',
                'keys',
                'sshUser',
                'sshPassword',
                'sshPort',
                'sshTunnel',
                'sshPublicKey',
                'sshPrivateKey',
                'lastError',
                'errors',
                'authenticated'
            );
        } else {
            $excludedParams = array(
                'user',
                'password',
                'port',
                'lastError',
                'errors',
                'authenticated'
            );
        }

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


    public function sendEmail($to, $fromPerson, $fromEmail, $subject, $body, $isHtml = false, $unsubUrl = null)
    {
        $res = $this->connect();

        if ($res == false) {
            $this->lastError = "Connect error: " . $this->getLastError();
            $this->lastStatus = 'error_connect';

            return false;
        }

        $res = $this->authenticate();

        if ($res == false) {
            $this->lastError = "Authenticate error: " . $this->getLastError();
            $this->lastStatus = 'error_authenticate';

            return false;
        }

        $response = $this->sendData("MAIL FROM: <{$fromEmail}>");
        $code     = $this->responseCode($response);

        if ($code != 250) {
            $this->lastError = "Error on MAIL FROM command: " . $response;
            $this->lastStatus = 'error_mail_from';

            return false;
        }

        $response = $this->sendData("RCPT TO: <{$to}>");
        $code     = $this->responseCode($response);

        if ($code != 250) {
            $this->lastError = "Error on RCPT TO command: " . $response;
            $this->lastStatus = 'error_rcpt_to_command';

            return false;
        }

        $response = $this->sendData("DATA");
        $code     = $this->responseCode($response);

        if ($code != 354) {
            $this->lastError = "Error on DATA command: " . $response;
            $this->lastStatus = 'error_data_command';

            return false;
        }

        $htmlHeader = "";

        if ($isHtml) {
            $htmlHeader = "Mime-Version: 1.0;{$this->newline}Content-Type: text/html;" .
                "charset='ISO-8859-1';{$this->newline}Content-Transfer-Encoding: 7bit;{$this->newline}";
            $htmlHeader .= "List-Unsubscribe: <{$unsubUrl}>{$this->newline}";
        }

        $response = $this->sendData(
            "Subject: {$subject}{$this->newline}To:<{$to}>{$this->newline}" .
            "From: {$fromPerson}<{$fromEmail}>{$this->newline}{$htmlHeader}" .
            "{$this->newline}{$this->newline}{$body}{$this->newline}."
        );

        $code     = $this->responseCode($response);

        if ($code != 250) {
            $this->lastError = "Error on sending data: " . $response;
            $this->lastStatus = 'error_send_data';

            return false;
        }

        $this->lastStatus = 'sent';

        $this->sendData('QUIT');
        fclose($this->socket);

        return true;
    }
    //--------------------------------------------------------------------------


    protected function connect()
    {
        if (!$this->host || !$this->port) {
            $this->lastError = "Required SMTP host and port";

            return false;
        }

        $recconceted = 0;

        while ($recconceted < $this->reconnectTime) {
            $this->socket = fsockopen(
                ($this->crypto ? $this->crypto . "://" : "") . $this->host,
                $this->port,
                $errno,
                $errstr,
                $this->timeout
            );

            $recconceted ++;

            if ($this->socket) {
                break 1;
            }
        }

        if ($this->socket === false) {
            $this->lastError = "Error connecting to SMTP server: {$errstr}";

            return false;
        }

        stream_set_timeout($this->socket, $this->timeout);

        $response = $this->readSocket();
        $code     = $this->responseCode($response);

        if ($code != 220) {
            $this->lastError = "Wrong response code on connect: " . $response;

            return false;
        }

        $response = $this->sendData("HELO" . ($this->myHost ? " {$this->myHost}" : ""));
        $code     = $this->responseCode($response);

        if ($code != 250) {
            $this->lastError = "Error on HELO command: " . $response;

            return false;
        }

        return true;
    }
    //--------------------------------------------------------------------------


    protected function authenticate()
    {
        if ($this->authenticated || (!$this->user && !$this->password)) {

            return true;
        }

        $response = $this->sendData("AUTH LOGIN");
        $code     = $this->responseCode($response);

        if ($code != 334) {
            $this->lastError = "Error on AUTH LOGIN command: " . $response;

            return false;
        }

        $response = $this->sendData(base64_encode($this->user));
        $code     = $this->responseCode($response);

        if ($code != 334) {
            $this->lastError = "Error on AUTH LOGIN username command: " . $response;

            return false;
        }

        $response = $this->sendData(base64_encode($this->password));
        $code     = $this->responseCode($response);

        if ($code != 235) {
            $this->lastError = "Error on AUTH LOGIN password command: " . $response;

            return false;
        }

        $this->authenticated = true;

        return true;
    }
    //--------------------------------------------------------------------------


    protected function sendData($data, $returnResponse = true)
    {
        $res = $this->writeSocket($data . $this->newline);

        if ($res === false) {
            return false;
        }

        if ($returnResponse) {
            return $this->readSocket();
        }

        return false;
    }
    //--------------------------------------------------------------------------


    protected function connectSSH()
    {
        if (!$this->keys) {
            $this->link = @ssh2_connect($this->host, $this->sshPort);
        } else {
            $this->link = @ssh2_connect($this->host, $this->sshPort, $this->sshHostKey);
        }

        if (!$this->link) {
            $this->errors['connect'] = 'Failed to connect to SSH2 Server ' . $this->host . ':' . $this->sshPort;

            return false;
        }

        if (!$this->keys) {
            if (!@ssh2_auth_password($this->link, $this->sshUser, $this->sshPassword)) {
                $this->errors['auth'] = 'Username/Password incorrect for ' . $this->sshUser;

                return false;
            }
        } else {
            if (!@ssh2_auth_pubkey_file(
                $this->link,
                $this->sshUser,
                $this->sshPublicKey,
                $this->sshPrivateKey,
                $this->sshPassword
            )) {
                $this->errors['auth'] = 'Public and Private keys incorrect for ' . $this->sshUser;

                return false;
            }
        }

        return true;
    }
    //--------------------------------------------------------------------------


    protected function runCommand($command, $returnbool = false)
    {
        if (!$this->link) {
            return false;
        }

        if (!($stream = ssh2_exec($this->link, $command))) {
            $this->errors['command'] = 'Unable to perform command: ' . $command;
        }

        return false;
    }
    //--------------------------------------------------------------------------


    protected function responseCode($response)
    {
        return @substr($response, 0, 3);
    }
    //--------------------------------------------------------------------------


    protected function responseMessage($response)
    {
        return @substr($response, 3);
    }
    //--------------------------------------------------------------------------


    protected function writeSocket($msg)
    {
        return fwrite($this->socket, $msg);
    }
    //--------------------------------------------------------------------------


    protected function readSocket()
    {
        return fgets($this->socket);
    }
    //--------------------------------------------------------------------------


    public function getLastError()
    {
        return $this->lastError;
    }
    //--------------------------------------------------------------------------


    public function getErrors()
    {

        return $this->errors;
    }
    //--------------------------------------------------------------------------


    public function getLastStatus()
    {

        return $this->lastStatus;
    }
    //--------------------------------------------------------------------------


    public function showErrors()
    {
        if (count($this->errors) > 1) {
            foreach ($this->errors as $key => $error) {
                trigger_error($error[$key], E_USER_ERROR);
            }
        } else {
            $errorKey = array_keys($this->errors);
            trigger_error($this->errors[$errorKey[0]], E_USER_ERROR);
        }

        return false;
    }
    //--------------------------------------------------------------------------
}
