<?php
namespace framework\extend\logger;

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
        return isset($this->$name) ? $this->$name : $this->$name = $this->$name();
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
        return $_SERVER['REMOTE_ADDR'];
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
    
    private static function date($format = null)
    {
        return $format ? date($format) : date("Y-m-d H:i:s");
    }
    
    private static function url()
    {
        return $_SERVER['REQUEST_URI'];
    }
    
    private static function referrer()
    {
        return $_SERVER['HTTP_REFERER'];
    }
}
