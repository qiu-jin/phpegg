<?php
namespace framework\driver\logger;

use framework\core\Event;
use framework\core\Container;
use framework\core\http\Request;
use framework\extend\view\Error;

class Email extends Logger
{
    protected $to;
    protected $email;
    protected $cache;
    protected $interval = 900;
    
    protected function init($config)
    {
        $this->to = $config['to'];
        $this->email = $config['email'];
        $this->cache = $config['cache'] ?? [
            'driver'=> 'opcache',
            'dir'   => APP_DIR.'storage/cache/',
        ];
        Event::on('close', [$this, 'send']);
    }
    
    public function write($level, $message, $context = null)
    {
        $this->logs[] = [$level, $message, $context];
    }
    
    public function send()
    {
        if (!$this->logs) return;
        $cache = Container::driver('cache', $this->cache);
        if ($cache) {
            $key = md5(jsonencode(end($this->logs)));
            if (!$cache->has($key)) {
                $cache->set($key, 1, $this->interval);
                $email = Container::driver('email', $this->email);
                if ($email) {
                    $title = Request::host().' Error report ['.date('Y-m-d H:i:s').']';
                    $content = Error::renderError($this->logs);
                    $email->send($this->to, $title, $content);
                }
            }
        }
        $this->logs = null;
    }
}