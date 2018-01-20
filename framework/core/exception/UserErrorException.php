<?php
namespace framework\core\exception;

class UserErrorException extends \ErrorException
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
