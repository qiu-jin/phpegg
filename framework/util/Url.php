<?php
namespace framework\util;

use framework\core\http\Request;

class Url
{
    private $url;
    
    private static $types = ['scheme', 'host', 'port', 'path', 'query', 'fragment'];
    
    public function __construct($url = null)
    {
        $this->url = is_array($url) ? $url : self::parse($url);
    }
    
    public static function current()
    {
        return new self(Request::server('REQUEST_SCHEME').'://'.Request::url());
    }
    
    public static function previous()
    {
        return new self(Request::server('HTTP_REFERER'));
    }
    
    public static function parse($url)
    {
        $arr = parse_url($url);
        if (isset($arr['path'])) {
            $arr['path'] = trim($arr['path'], '/');
        }
        if (isset($arr['query'])) {
            parse_str($arr['query']);
        }
        return $arr;
    }
    
    public function __get($name)
    {
        if (in_array($name, self::$types)) {
            return $this->url[$name] ?? null;
        }
        throw new \Exception("Undefined property: $$name");
    }
    
    public function __set($name, $value)
    {
        if (in_array($name, self::$types)) {
            $this->url[$name] = $value;
        } else {
            throw new \Exception("Undefined property: $$name");
        }
    }
    
    public function make()
    {
        foreach (self::$types as $type) {
            if (isset($this->url[$type])) {
                $url .= $this->build($type, $this->url[$type]);
            }
        }
        return $url ?? false;
    }
    
    public function toArray()
    {
        return $this->url;
    }
    
    public function __toString()
    {
        return $this->make();
    }
    
    private function build($type, $value)
    {
        switch ($type) {
            case 'scheme':
                return "$value://";
            case 'host':
                return $value;
            case 'port':
                return ":$value";
            case 'path':
                return '/'.trim($value).'/';
            case 'query':
                return '?'.http_build_query($value);
            case 'fragment':
                return "#$value";
        }
    }
}
