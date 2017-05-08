<?php
namespace framework\driver\email;

use framework\driver\email\message\Mime;

class Smtp extends Email
{
    protected $ch;
    protected $host;
    protected $port = 25;
    protected $username;
    protected $password;
    
    protected function init($config)
    {
        $this->host = $config['host'];
        if (isset($config['port']) ) {
            $this->port = $config['port'] ;
        }
        $this->username = $config['username'];
        $this->password = $config['password'];
    }
    
    protected function handle()
    {
        $this->log = [];
        if ($this->connect()) {
            try {
                list($addrs, $mime) = Mime::build($this->option);
            } catch (\Exception $e) {
                $this->log['MIME'] = $e->getMessage();
                return false;  
            }
            $this->log['FROM'] = $this->command('MAIL FROM: <'.$this->option['from'][0].'>');
            if (substr($this->log['FROM'], 0, 3) != '250') {
                return false; 
            }
            foreach ($addrs as $addr) {
                $this->log['RCPT'] = $this->command("RCPT TO: <$addr>");
                if (substr($this->log['RCPT'], 0, 3) != '250') {
                    return false;
                }
            }
            $this->log['DATA'] = $this->command('DATA');
            $this->log['SEND'] = $this->command($mime."\r\n.");
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
    
    public function __destruct()
    {
        $this->ch && fclose($this->ch);
    }
}