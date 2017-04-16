<?php
namespace framework\driver\logger;

use framework\core\Hook;
use framework\extend\view\Error;

class Email extends Logger
{
    protected $to;
    protected $driver = [];
    protected $interval = 3600;
    
    public function __construct($config)
    {
        if (isset($config['to']) && isset($config['email'])) {
            $this->to = $config['to'];
            $this->driver['email'] = $config['email'];
            if (isset($config['cache'])) {
                $this->driver['cache'] = $config['cache'];
            }
            if (isset($config['interval'])) {
                $this->interval = $config['interval'];
            }
            Hook::add('close', [$this, 'send']);
        } else {
            $this->send = false;
        }
    }
    
    public function write($level, $message, $context)
    {
        if ($this->send) $this->logs[] = [$level, $message, $context];
    }
    
    public function send()
    {
        if ($this->logs) {
            $cache = $this->gethandler('cache');
            if ($cache) {
                $key = md5(json_encode(end($this->logs)));
                if (!$cache->has($key)) {
                    $cache->set($key, time(), $this->interval);
                    $title = APP_NAME.' Error report ['.date('Y-m-d H:i:s').']';
                    $content = Error::page($this->logs);
                    $email = $this->gethandler('email');
                    if ($email) {
                        $email->send($to, $title, $content);
                    }
                }
            }
            $this->logs = null;
        }
    }
    
    protected function gethandler($type)
    {
        if (isset($this->driver[$type]) {
            return load($type, isset($this->driver[$type]);
        } elseif ($type === 'cache') {
            return driver('cache', 'SingleFile', ['file' => APP_DIR.'storage/cache/logger_email.cache']);
        }
        return null;
    }
}