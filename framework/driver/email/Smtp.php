<?php
namespace framework\driver\email;

use framework\core\Logger;
use framework\driver\email\query\Mime;

class Smtp extends Email
{
    protected $sock;
    protected $debug = APP_DEBUG;
    
    protected function init($config)
    {
        if (isset($config['debug'])) {
            $this->debug = $config['debug'];
        }
        $this->sock = fsockopen($config['host'], $config['port'] ?? 25, $errno, $error, $config['timeout'] ?? 15);
        if (!is_resource($this->sock)) {
            throw new \Exception("Smtp connect error: [$errno] $error");
        }
        $this->read();
        $this->command('EHLO '.$config['host']);
        $this->command('AUTH LOGIN');
        $this->command(base64_encode($config['username']));
        $res = $this->command(base64_encode($config['password']));
        if (substr($res, 0, 3) !== '235') {
            throw new \Exception("Smtp auth error: $res");
        }
    }
    
    public function handle($options)
    {
        $mime = Mime::build($options, $addrs);
        $res = $this->command("MAIL FROM: <{$options['from'][0]}>");
        if (substr($res, 0, 3) != '250') {
            return warn($res);
        }
        foreach ($addrs as $addr) {
            $res = $this->command("RCPT TO: <$addr>");
            if (substr($res, 0, 3) != '250') {
                return warn($res);
            }
        }
        $this->command('DATA');
        $res = $this->command($mime.Mime::EOL.".");
        if (substr($res, 0, 3) != '250') {
            return warn($res);
        }
        $this->command('QUIT');
        return true;
    }
    
    protected function read()
    {
        $res = '';
        while ($str = fgets($this->sock, 1024)) {
            $res .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        $res = trim($res);
        if ($this->debug) {
            logger::write(Logger::DEBUG, $res);
        }
        return $res;
    }
    
    protected function command($cmd)
    {
        fputs($this->sock, $cmd.Mime::EOL);
        return $this->read();
    }
    
    public function __destruct()
    {
        empty($this->sock) || fclose($this->sock);
    }
}