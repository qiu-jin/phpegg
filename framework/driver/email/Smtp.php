<?php
namespace framework\driver\email;

use framework\core\Logger;
use framework\driver\email\message\Mime;

class Smtp extends Email
{
    protected $fp;
    protected $debug;
    
    protected function init($config)
    {
        $this->fp = fsockopen($config['host'], $config['port'] ?? 25, $errno, $error, $config['timeout'] ?? 15);
        if (!is_resource($this->fp)) {
            throw new \Exception("Smtp connect error: [$errno]$error");
        }
        $this->read();
        $this->command('EHLO '.$config['host']);
        $this->command('AUTH LOGIN');
        $this->command(base64_encode($config['username']));
        $data = $this->command(base64_encode($config['password']));
        if (substr($data, 0, 3) !== '235') {
            throw new \Exception("Smtp auth error: $data");
        }
        $this->debug = $config['debug'] ?? APP_DEBUG;
    }
    
    public function handle($options)
    {
        list($addrs, $mime) = Mime::build($options);
        $data = $this->command("MAIL FROM: <{$options['from'][0]}>");
        if (substr($data, 0, 3) != '250') {
            return warning($data);
        }
        foreach ($addrs as $addr) {
            $data = $this->command("RCPT TO: <$addr>");
            if (substr($data, 0, 3) != '250') {
                return warning($data);
            }
        }
        $this->command('DATA');
        $data = $this->command($mime.Mime::EOL.".");
        if (substr($data, 0, 3) != '250') {
            return warning($data);
        }
        $this->command('QUIT');
        return true;
    }
    
    protected function read()
    {
        $res = '';
        while ($str = fgets($this->fp, 1024)) {
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
        fputs($this->fp, $cmd.Mime::EOL);
        return $this->read();
    }
    
    public function debug(bool $bool)
    {
        $this->debug = $bool;
        return $this;
    }
    
    public function __destruct()
    {
        $this->fp && fclose($this->fp);
    }
}