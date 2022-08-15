<?php
namespace framework\driver\logger;

use framework\util\Str;
use framework\util\Arr;
use framework\util\Date;
use framework\core\Event;
use framework\core\Container;

class Email extends Logger
{
    // 日志收件人
    protected $to;
    // 邮件驱动配置
    protected $email;
    // 缓存驱动配置
    protected $cache = [
        'driver'=> 'file',
		'ext'	=> '.send_email_log_cache.txt',
        'dir'	=> APP_DIR.'storage/cache/',
    ];
	// 邮件标题模版
	protected $title = "[{time}] Error report: {key}";
    // 邮件发送间隔时间（秒数）
    protected $send_interval = 900;
    
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
        Event::on('exit', [$this, 'flush']);
    }
	
    public function write($level, $message, $context = null)
    {
        $this->logs[] = [$level, $message, $context];
    }
    
    /*
     * 输出缓冲
     */
    public function flush()
    {
        if ($this->logs) {
            $log = Arr::last($this->logs);
            $key = md5($log[0].$log[1].($log[2]['file'] ?? '').($log[2]['line'] ?? ''));
			$cache = Container::driver('cache', $this->cache);
            if (!$cache->has($key)) {
                $cache->set($key, 1, $this->send_interval);
				$title = Str::format($this->title, ['key' => $key, 'time' => Date::now()->format()]);
				$content = json_encode($this->logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
				Container::driver('email', $this->email)->send($this->to, $title, $content);
            }
            $this->logs = null;
        }
    }
}