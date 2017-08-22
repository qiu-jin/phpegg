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
            $cache = cache($this->driver['cache']);
            if ($cache) {
                $content = Error::renderError($this->logs);
                $key = md5($content);
                if (!$cache->has($key)) {
                    $cache->set($key, 1, $this->interval);
                    $title = Request::host().' Error report ['.date('Y-m-d H:i:s').']';
                    $email = email($this->driver['email']);
                    if ($email) {
                        $email->send($this->to, $title, $content);
                    }
                }
            }
            $this->logs = null;
        }
    }
}