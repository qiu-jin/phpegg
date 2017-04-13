<?php
namespace framework\driver\logger;

class File extends Logger
{   
    private $logfile;
    
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
        if (isset($this->formater)) {
            $log = $this->formater->make($level, $message, $context);
        } else {
            $log = '['.$level.'] '.$message;
            if ($context) $log .= json_encode($context);
        }
        error_log((string) $log."\r\n", 3, $this->logfile);
    }
}
