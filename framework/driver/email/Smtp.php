<?php
namespace framework\driver\email;

use framework\core\Logger;
use framework\driver\email\message\Mime;

class Smtp extends Email
{
    protected $link;
    protected $host;
    protected $port;
    protected $debug = APP_DEBUG;
    protected $username;
    protected $password;
    
    protected function init($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'] ?? 25;
        $this->username = $config['username'];
        $this->password = $config['password'];
    }
    
    public function handle($options)
    {
        if ($this->connect()) {
            list($addrs, $mime) = Mime::build($options);
            $data = $this->command('MAIL FROM: <'.$options['from'][0].'>');
            if (substr($data, 0, 3) != '250') {
                return error($data);
            }
            foreach ($addrs as $addr) {
                $data = $this->command("RCPT TO: <$addr>");
                if (substr($data, 0, 3) != '250') {
                    return error($data);
                }
            }
            $this->command('DATA');
            $data = $this->command($mime.Mime::EOL.".");
            if (substr($data, 0, 3) != '250') {
                return error($data);
            }
            $this->command('QUIT');
            return true;
        }
        return false;
    }
    
    protected function connect()
    {
        if (!$this->link) {
            $this->link = fsockopen($this->host, $this->port, $errno, $error, 15);
            if (!is_resource($this->link)) {
                return error('connect error '.$errno.': '.$error);
            }
            $this->read();
            $this->command('EHLO '.$this->host);
            $this->command('AUTH LOGIN');
            $this->command(base64_encode($this->username));
            $data = $this->command(base64_encode($this->password));
            if (substr($data, 0, 3) != '235') {
                return error($data);
            }
        }
        return true;
    }
    
    protected function read()
    {
        $res = '';
        while ($str = fgets($this->link, 1024)) {
            $res .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        $res = trim($res);
        if ($this->debug) {
            logger::write(Logger::DEBUG, $res);
        }
        return $res;
    }
    
    protected function command($cmd)
    {
        fputs($this->link, $cmd.Mime::EOL);
        return $this->read();
    }
    
    public function debug(bool $bool)
    {
        $this->debug = $bool;
        return $this;
    }
    
    public function __destruct()
    {
        $this->link && fclose($this->link);
    }
}