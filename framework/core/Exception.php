<?php
namespace framework\core;

class Exception extends \Exception
{
    protected $data;
    protected $class;
    
    public function __construct($message, $class = null, $data = null)
    {
        $this->data     = $data;
        $this->class    = $class;
        $this->message  = $message;
    }
    
    public static function __callStatic($name, $params)
    {
        return new self(...$params);
    }
    
    public function getClass()
    {
        return $this->class;
    }
    
    public function setClass($class)
    {
        $this->class = $class;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function setData($data)
    {
        $this->data = $data;
    }
}
