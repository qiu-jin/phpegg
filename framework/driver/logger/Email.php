<?php
namespace framework\driver\logger;

use framework\util\Arr;
use framework\core\Event;
use framework\core\Container;
use framework\core\misc\ViewError;

class Email extends Logger
{
    // 日志收件人
    protected $to;
    // 邮件驱动配置
    protected $email;
    // 缓存驱动配置
    protected $cache = [
        'driver'	=> 'opcache',
        'dir'       => APP_DIR.'storage/cache/',
    ];
    // 邮件发送间隔时间（秒数）
    protected $send_interval = 600;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->to = $config['to'];
        $this->email = $config['email'];
        if (isset($config['cache'])) {
            $this->cache = $config['cache'];
        }
        if (isset($config['send_interval'])) {
            $this->send_interval = $config['send_interval'];
        }
        Event::on('close', [$this, 'flush']);
    }
    
    /*
     * 输出缓冲
     */
    public function flush()
    {
        if ($this->logs) {
            if ($cache = Container::driver('cache', $this->cache)) {
                $log = Arr::last($this->logs);
                $key = md5($log[0].$log[1].($log[2]['file'] ?? '').($log[2]['line'] ?? ''));
                if (!$cache->has($key)) {
                    $cache->set($key, '', $this->interval);
                    if ($email = Container::driver('email', $this->email)) {
                        $title = "Error report: $key";
                        $content = ViewError::renderError($this->logs);
                        $email->send($this->to, $title, $content);
                    }
                }
            }
            $this->logs = null;
        }
    }
}