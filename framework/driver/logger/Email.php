<?php
namespace framework\driver\logger;

use framework\core\Event;
use framework\core\Container;
use framework\extend\view\Error;

class Email extends Logger
{
    // 日志收件人
    protected $to;
    // 邮件驱动配置
    protected $email;
    // 缓存驱动配置
    protected $cache;
    // 邮件发送间隔时间（秒数）
    protected $interval;
    
    public function __construct($config)
    {
        $this->to       = $config['to'];
        $this->email    = $config['email'];
        $this->cache    = $config['cache'] ?? [
            'driver'    => 'opcache',
            'dir'       => APP_DIR.'storage/cache/',
        ];
        $this->interval = $config['interval'] ?? 3600;
        Event::on('close', [$this, 'flush']);
    }
    
    public function flush()
    {
        if ($this->logs) {
            if ($cache = Container::driver('cache', $this->cache)) {
                $key = md5(json_encode(end($this->logs)));
                if (!$cache->has($key)) {
                    $cache->set($key, 1, $this->interval);
                    if ($email = Container::driver('email', $this->email)) 
                        $title = 'Error report ['.date('Y-m-d H:i:s').']';
                        $content = Error::renderError($this->logs);
                        $email->send($this->to, $title, $content);
                    }
                }
            }
            $this->logs = null;
        }
    }
}