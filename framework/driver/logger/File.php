<?php
namespace framework\driver\logger;

class File extends Logger
{   
    protected $logfile;
    
    protected function init($config)
    {
        $this->logfile = $config['logfile'];
    }
    
    public function write($level, $message, $context = null)
    {
        if ($this->formatter) {
            $log = $this->formatter->make($level, $message, $context);
        } else {
            $log = "[$level] $message".PHP_EOL;
            if ($context) {
                $log .= var_export($context, true).PHP_EOL;
            }
        }
        error_log($log, 3, $this->logfile);
    }
}
