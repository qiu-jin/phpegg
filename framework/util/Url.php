<?php
namespace framework\util;

use framework\core\http\Request;

class Url
{
    private $url;
    private static $types = ['scheme', 'host', 'port', 'path', 'query', 'fragment'];
    
    public function __construct($url = null)
    {
        if (is_string($url)) {
            $this->url = self::parse($url);
        } elseif (is_array($url)) {
            $this->url = $url;
        }
    }
    
    public static function current()
    {
        return new self(Request::server('REQUEST_SCHEME').'://'.Request::url());
    }
    
    public static function previous()
    {
        return new self(Request::server('HTTP_REFERER'));
    }
    
    public static function parse($str)
    {
        $url = parse_url($str);
        if (isset($url['path'])) {
            $url['path'] = trim($url['path'], '/');
        }
        if (isset($url['query'])) {
            parse_str($url['query'], $url['query']);
        }
        return $url;
    }
    
    public function __get($name)
    {
        if (!in_array($name, self::$types)) {
            throw new \Exception('Undefined property: $'.$name);
        }
        return $this->url[$name] ?? null;
    }
    
    public function __set($name, $value)
    {
        if (!in_array($name, self::$types)) {
            throw new \Exception('Undefined property: $'.$name);
        }
        $this->url[$name] = $value;
    }
    
    public function make()
    {
        $url = '';
        foreach (self::$types as $type) {
            if (isset($this->url[$type])) {
                $url .= $this->build($type, $this->url[$type]);
            }
        }
        return $url;
    }
    
    public function toArray()
    {
        return $this->url;
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
