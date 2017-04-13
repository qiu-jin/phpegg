<?php
namespace framework\driver\email;

use framework\extend\email\Mime;

class Smtp extends Email
{
    protected $ch;
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    
    protected function init($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }
    
    public function send($to, $subject, $content)
    {
        $this->log = [];
        if ($this->connect()) {
            try {
                $data = Mime::build($to, $subject, $content, $this->option);
            } catch (\Exception $e) {
                $this->log['MIME'] = $e->getMessage();
                return false;  
            }
            $this->log['FROM'] = $this->command('MAIL FROM: <'.$this->option['from'][0].'>');
            if (substr($this->log['FROM'], 0, 3) != '250') {
                return false; 
            }
            foreach ($data[0] as $addr) {
                $this->log['RCPT'] = $this->command("RCPT TO: <$addr>");
                if (substr($this->log['RCPT'], 0, 3) != '250') {
                    return false;
                }
            }
            $this->log['DATA'] = $this->command('DATA');
            $this->log['SEND'] = $this->command($data[1]."\r\n.");
            if (substr($this->log['SEND'], 0, 3) != '250') {
                return false;
            }
            $this->log['QUIT'] = $this->command('QUIT');
            fclose($this->ch);
            return true;
        }
        return false;
    }
    
    protected function connect()
    {
        $this->ch = fsockopen($this->host, $this->port, $errno, $error, 15);
        if (!is_resource($this->ch)) {
            $this->log['CONN'] = 'connect error '.$errno.': '.$error;
            return false;
        }
        $this->log['OPEN'] = $this->read();
        $this->log['EHLO'] = $this->command('EHLO '.$this->host);
        $this->log['AUTH'] = $this->command('AUTH LOGIN');
        $this->log['USER'] = $this->command(base64_encode($this->username));
        $this->log['PSWD'] = $this->command(base64_encode($this->password));
        if (substr($this->log['PSWD'], 0, 3) != '235') {
            return false;
        }
        return true;
    }
    
    protected function read()
    {
        $res = '';
        while ($str = fgets($this->ch, 4096)) {
            $res .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $res;
    }
    
    protected function command($cmd)
    {
        fputs($this->ch, "$cmd\r\n");
        return $this->read();
    }
}