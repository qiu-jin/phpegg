<?php
namespace framework\driver\logger;

use framework\core\Event;

class File extends Logger
{
    // 日志文件
    protected $logfile;
    // 日志格式化处理器
    protected $formatter;
    // 是否实时写入日志
    protected $real_write = false;
    
    public function __construct($config)
    {
        $this->logfile = $config['logfile'];
        if (isset($config['real_write'])) {
            $this->real_write = $config['real_write'];
        }
        if (!$this->real_write) {
            Event::on('close', [$this, 'flush']);
        }
        $this->formatter = new formatter\Formatter(
            $config['format'] ?? "[{date}] [{level}] {message}",
            $config['format_options'] ?? null
        );
    }
    
    public function write($level, $message, $context = null)
    {
        if ($this->real_write) {
            $this->realWrite($level, $message, $context);
        } else {
            $this->logs[] = [$level, $message, $context];
        }
    }
    
    public function flush()
    {
        if ($this->logs) {
            foreach ($this->logs as $log) {
                $this->realWrite(...$log);
            }
            $this->logs = null;
        }
    }
    
    protected function realWrite($level, $message, $context)
    {
        error_log($this->formatter->make($level, $message, $context).PHP_EOL, 3, $this->logfile);
    }
}
