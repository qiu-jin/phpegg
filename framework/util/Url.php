<?php
namespace framework\util;

use framework\core\http\Request;

class Url
{
    private $url;
    private static $types = ['scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment'];
    
    public function __construct($url)
    {
        if (is_string($url)) {
            $this->url = parse_url($url);
            if (isset($this->url['path'])) {
                $this->url['path'] = trim($this->url['path'], '/');
            }
            if (isset($this->url['query'])) {
                parse_str($this->url['query'], $this->url['query']);
            }
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
                $url .= $this->build($type, $this->url[$item]);
            }
        }
    }
    
    private function build($type, $value)
    {
        //'scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment'
        switch ($type) {
            case 
        }
    }
}
