<?php
namespace framework\core;

class Exception extends \Exception
{
    protected $data;
    protected $class;
    protected $method;
    
    public function __construct($message, $class = null, $method = null, $data = null)
    {
        $this->data     = $data;
        $this->class    = $class;
        $this->method   = $method;
        $this->message  = $message;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function setData($data)
    {
        $this->data = $data;
    }
    
    public function getClass()
    {
        return $this->class;
    }
    
    public function setClass($class)
    {
        $this->class = $class;
    }
    
    public function getMethod()
    {
        return $this->method;
    }
    
    public function seMethod($method)
    {
        $this->method = $method;
    }
}
