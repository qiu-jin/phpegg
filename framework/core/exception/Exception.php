<?php
namespace framework\core;

class Exception extends \Exception
{
    protected $class = __CLASS__;
    
    public function __construct($message, $class = null)
    {
        if ($class !== null) {
            $this->class = $class;
        }
        $this->message  = $message;
    }
    
    public function getClass()
    {
        return $this->class;
    }
    
    public function setClass($class)
    {
        $this->class = $class;
    }
}
