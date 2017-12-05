<?php
namespace framework\driver\logger\formatter;

use framework\core\http\Request;

class Formatter
{
    private $format;
    private $replace = [];
    
    public function __construct($format)
    {
        $this->format = $format;
        if (preg_match_all('/\{(\w+)\}/', $format , $matchs)) {
            foreach (array_unique($matchs[1]) as $var) {
                if (method_exists($this, $var)) {
                    $this->replace['{'.$var.'}'] = $this->$var();
                } else {
                    $this->replace['{'.$var.'}'] = '';
                }
            }
        }
    }
    
    public function _get($name)
    {
        return $this->$name ?? $this->$name = $this->$name();
    }
    
    public function make($level, $message, $context)
    {
        $replace = $this->replace;
        $replace['{level}'] = $level;
        $replace['{message}'] = $message;
        if ($context) {
            foreach ($context as $k => $v) {
                $replace['{'.$k.'}'] = $v;
            }
        }
        return strtr($this->format, $replace);
    }
    
    private static function ip()
    {
        return Request::ip();
    }
    
    private static function pid()
    {
        return getmypid();
    }
    
    private static function uuid()
    {
        return uniqid();
    }
    
    private static function time()
    {
        return time();
    }
    
    private static function date()
    {
        return date("Y-m-d H:i:s");
    }
    
    private static function url()
    {
        return Request::url();
    }
    
    private static function referrer()
    {
        return Request::header('referrer');
    }
}
