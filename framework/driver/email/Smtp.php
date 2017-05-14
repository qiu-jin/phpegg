<?php
namespace framework\driver\email;

use framework\core\Error;
use framework\driver\email\message\Mime;

class Smtp extends Email
{
    protected $link;
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    
    protected function init($config)
    {
        $this->host = $config['host'];
        $this->port = isset($config['port']) ? $config['port'] : 25;
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
                return (bool) Error::set($e->getMessage()); 
            }
            $this->log['FROM'] = $this->command('MAIL FROM: <'.$this->option['from'][0].'>');
            if (substr($this->log['FROM'], 0, 3) != '250') {
                return (bool) Error::set($this->log['FROM']);
            }
            foreach ($addrs as $addr) {
                $this->log['RCPT'] = $this->command("RCPT TO: <$addr>");
                if (substr($this->log['RCPT'], 0, 3) != '250') {
                    return (bool) Error::set($this->log['RCPT']);
                }
            }
            $this->log['DATA'] = $this->command('DATA');
            $this->log['SEND'] = $this->command($mime."\r\n.");
            if (substr($this->log['SEND'], 0, 3) != '250') {
                return (bool) Error::set($this->log['SEND']);
            }
            $this->log['QUIT'] = $this->command('QUIT');
            return true;
        }
        return false;
    }
    
    protected function connect()
    {
        if (!$this->link) {
            $this->link = fsockopen($this->host, $this->port, $errno, $error, 15);
            if (!is_resource($this->link)) {
                return (bool) Error::set('connect error '.$errno.': '.$error);
            }
            $this->log['OPEN'] = $this->read();
            $this->log['EHLO'] = $this->command('EHLO '.$this->host);
            $this->log['AUTH'] = $this->command('AUTH LOGIN');
            $this->log['USER'] = $this->command(base64_encode($this->username));
            $this->log['PSWD'] = $this->command(base64_encode($this->password));
            if (substr($this->log['PSWD'], 0, 3) != '235') {
                return (bool) Error::set($this->log['PSWD']);
            }
        }
        return true;
    }
    
    protected function read()
    {
        $res = '';
        while ($str = fgets($this->link, 4096)) {
            $res .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $res;
    }
    
    protected function command($cmd)
    {
        fputs($this->link, "$cmd\r\n");
        return $this->read();
    }
    
    public function __destruct()
    {
        $this->link && fclose($this->link);
    }
}