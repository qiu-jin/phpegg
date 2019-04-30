<?php
namespace framework\exception;

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
    
    public static function export($message, $data)
    {
        return new self($message.var_export($data, true));
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
