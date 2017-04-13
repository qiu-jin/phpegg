<?php
namespace Framework\Extend\Logger;

class Formatter
{
    private $format = [];
    
    public function __construct($format)
    {
        if (preg_match_all('/\{(\w+)\}/', $format , $matchs)) {
            foreach (array_unique($matchs[1]) as $var) {
                if (method_exists($this, $var)) {
                    $this->format['{'.$var.'}'] = $this->$var();
                }
            }
        }
    }
    
    public function make($log)
    {
        return ;
    }
    
    private function ip()
    {
        return $_SERVER['REMOTE_ADDR'];
    }
    
    private function pid()
    {
        return getmypid();
    }
    
    private function uuid()
    {
        return uniqid();
    }
    
    private function time()
    {
        return time();
    }
    
    private function date($format = null)
    {
        return $format ? date($format) : date("Y-m-d H:i:s");
    }
    
    private function url()
    {
        return $_SERVER['REQUEST_URI'];
    }
    
    private function referrer()
    {
        return $_SERVER['HTTP_REFERER'];
    }
}
