<?php
namespace framework\driver\logger;

use framework\core\Hook;
use framework\core\http\Request;
use framework\extend\view\Error;

class Email extends Logger
{
    protected $to;
    protected $driver = [
        'email' => null,
        'cache' => [
            'driver'=> 'opcache',
            'dir'   => APP_DIR.'storage/cache/',
        ]
    ];
    protected $interval = 900;
    
    public function __construct($config)
    {
        if (isset($config['to'])) {
            $this->to = $config['to'];
            if (isset($config['email'])) {
                $this->driver['email'] = $config['email'];
            }
            if (isset($config['cache'])) {
                $this->driver['cache'] = $config['cache'];
            }
            if (isset($config['interval'])) {
                $this->interval = (int) $config['interval'];
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
            try {
                $cache = cache($this->driver['cache']);
                if ($cache) {
                    $key = md5(jsonencode(end($this->logs)));
                    if (!$cache->has($key)) {
                        $cache->set($key, 1, $this->interval);
                        $email = email($this->driver['email']);
                        if ($email) {
                            $title = Request::host().' Error report ['.date('Y-m-d H:i:s').']';
                            $content = Error::renderError($this->logs);
                            $email->send($this->to, $title, $content);
                        }
                    }
                }
            } catch (\Throwable $e) {
                //忽略异常
            }
            $this->logs = null;
        }
    }
}