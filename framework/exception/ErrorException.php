<?php
namespace framework\exception;

class ErrorException extends \ErrorException
{
    protected $trace;
    
    public function __construct($message, $severity, $file, $line, $trace)
    {
        $this->trace = $trace;
        parent::__construct($message, 0, $severity, $file, $line);
    }
    
    public function getUsertrace()
    {
        return $this->trace;
    }
}
