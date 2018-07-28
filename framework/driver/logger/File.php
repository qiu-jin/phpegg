<?php
namespace framework\driver\logger;

use framework\core\Event;

class File extends Logger
{   
    protected $logfile;
    protected $real_write;
    
    protected function init($config)
    {
        $this->logfile = $config['logfile'];
        if (!$this->real_write = $config['real_write'] ?? false) {
            Event::on('close', [$this, 'flush']);
        }
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
        if ($this->formatter) {
            $log = $this->formatter->make($level, $message, $context);
        } else {
            $log = "[$level] $message";
            if ($context) {
                $log .= PHP_EOL.var_export($context, true);
            }
        }
        error_log($log.PHP_EOL, 3, $this->logfile);
    }
}
