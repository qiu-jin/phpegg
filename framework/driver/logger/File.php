<?php
namespace framework\driver\logger;

class File extends Logger
{   
    protected $logfile;
    
    public function __construct($config)
    {
        if (isset($config['logfile']) && is_dir(dirname($config['logfile']))) {
            $this->logfile = $config['logfile'];
        } else {
            $this->send = false;
        }
    }
    
    public function write($level, $message, $context)
    {
        if (!$this->send) return;
        if (isset($this->formatter)) {
            $log = $this->formatter->make($level, $message, $context);
        } else {
            $log = '['.$level.'] '.$message;
            if ($context) $log .= PHP_EOL.var_export($context, true);
        }
        error_log($log.PHP_EOL, 3, $this->logfile);
    }
}
