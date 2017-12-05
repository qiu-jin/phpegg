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
        if (isset($this->formatter)) {
            $log = $this->formatter->make($level, $message, $context);
        } else {
            $log = '['.$level.'] '.$message;
            if ($context) $log .= PHP_EOL.var_export($context, true);
        }
        error_log($log.PHP_EOL, 3, $this->logfile);
    }
}
