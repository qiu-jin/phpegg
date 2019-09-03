<?php
namespace framework\driver\logger;

use framework\core\Event;
use framework\driver\logger\formatter\Formatter;

class File extends Logger
{
    // 日志文件
    protected $logfile;
    // 日志格式化处理器
    protected $formatter;
    // 是否实时写入日志
    protected $realtime_write = false;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->logfile = $config['logfile'];
        if (isset($config['realtime_write'])) {
            $this->realtime_write = $config['realtime_write'];
        }
        if (!$this->realtime_write) {
            Event::on('close', [$this, 'flush']);
        }
		if (isset($config['formatter'])) {
			$f = $config['formatter'];
			$class = $f['class'] ?? Formatter::class;
			$this->formatter = new $class($f['format'] ?? null, $f['options'] ?? null);
		}
    }
    
    /*
     * 写入
     */
    public function write($level, $message, $context = null)
    {
        if ($this->realtime_write) {
            $this->realWrite($level, $message, $context);
        } else {
            $this->logs[] = [$level, $message, $context];
        }
    }
	
    /*
     * 输出缓冲
     */
    public function flush()
    {
        if ($this->logs) {
            foreach ($this->logs as $log) {
                $this->realWrite(...$log);
            }
            $this->logs = null;
        }
    }
    
    /*
     * 实时写入
     */
    protected function realWrite($level, $message, $context)
    {
		$log = $this->formatter ? $this->formatter->format($level, $message, $context) : $message;
        error_log($log.PHP_EOL, 3, $this->logfile);
    }
}
