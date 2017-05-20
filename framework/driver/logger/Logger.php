<?php
namespace framework\driver\logger;

use framework\extend\logger\Formatter;

abstract class Logger
{
    use \framework\extend\logger\Writer;
    
    protected $send = true;
    protected $formatter;
    
    abstract public function write($level, $message, $context);
    
    public function setFormatter(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }
}
