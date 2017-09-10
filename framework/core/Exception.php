<?php
namespace framework\core;

class Exception extends \Exception
{
    protected $class;
    
    public function __construct($message, $class = null)
    {
        $this->class    = $class;
        $this->message  = $message;
    }
    
    public static function __callStatic($name, $params)
    {
        $e = new self(...$params);
        $e->class = ucfirst($name).'Exception';
        return $e;
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
