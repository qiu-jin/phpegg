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
    
    public function getData()
    {
        return $this->data;
    }
    
    public function getClass()
    {
        return $this->class ?? __CLASS__;
    }
}
