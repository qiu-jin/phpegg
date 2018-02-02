<?php
namespace framework\driver\logger\formatter;

use framework\core\http\Request;

class Formatter
{
    private $format;
    private $replace;
    private $options = [
        'proxy_ip'      => false,
        'date_format'   => 'Y-m-d H:i:s',
    ];
    
    public function __construct($format, array $options = null)
    {
        $this->format  = $format;
        if ($options) {
            $this->options = $options + $this->options;
        }
        if (preg_match_all('/\{(\w+)\}/', $format , $matchs)) {
            foreach (array_unique($matchs[1]) as $var) {
                $method = "get$var";
                if (method_exists($this, $method)) {
                    $this->replace['{'.$var.'}'] = $this->$method();
                } else {
                    $this->replace['{'.$var.'}'] = '';
                }
            }
        }
    }
    
    public function make($level, $message, $context = null)
    {
        $replace = [
            '{level}'   => $level,
            '{message}' => $message
        ] + $this->replace;
        if ($context) {
            foreach ($context as $k => $v) {
                $replace['{'.$k.'}'] = $v;
            }
        }
        return strtr($this->format, $replace);
    }
    
    private function getIp()
    {
        return Request::ip($this->options['proxy_ip']);
    }
    
    private function getPid()
    {
        return getmypid();
    }
    
    private function getUuid()
    {
        return uniqid();
    }
    
    private function getTime()
    {
        return time();
    }
    
    private function getDate()
    {
        return date($this->options['date_format']);
    }
    
    private function getUrl()
    {
        return Request::url();
    }
    
    private function getReferrer()
    {
        return Request::header('referrer');
    }
}
