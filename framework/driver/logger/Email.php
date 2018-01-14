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
    protected $interval;
    
    protected function init($config)
    {
        $this->to       = $config['to'];
        $this->email    = $config['email'];
        $this->cache    = $config['cache'] ?? [
            'driver'    => 'opcache',
            'dir'       => APP_DIR.'storage/cache/',
        ];
        $this->interval = $config['interval'] ?? 900;
        Event::on('close', [$this, 'send']);
    }
    
    public function send()
    {
        if (!$this->logs) {
            return;
        }
        if ($cache = Container::driver('cache', $this->cache)) {
            $key = md5(json_encode(end($this->logs)));
            if (!$cache->has($key)) {
                $cache->set($key, 1, $this->interval);
                if ($email = Container::driver('email', $this->email)) {
                    $title = Request::host().' Error report ['.date('Y-m-d H:i:s').']';
                    $content = Error::renderError($this->logs);
                    $email->send($this->to, $title, $content);
                }
            }
        }
        $this->logs = null;
    }
}