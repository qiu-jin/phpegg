<?php
namespace framework\core;

class Exception extends \Exception
{
    protected $data;
    
    public function __construct($message, $code = null, $file = null, $line = null)
    {
        $this->message = $message;
        if (isset($code)) {
            $this->code = $code;
        }
        if (isset($file)) {
            $this->file = $file;
        }
        if (isset($line)) {
            $this->line = $line;
        }
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function setData($data)
    {
        $this->data = $data;
    }
    
    public function getName($code = null)
    {
        if (!$code) {
            $code = $this->getCode();
        }
        if ($code && isset(self::$code_name[$code])) {
            return self::$code_name[$code];
        }
        return null;
    }
    
    public static function setName($code, $name)
    {
        self::$code_name[$code] = $name;
    }
}
