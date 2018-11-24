<?php
namespace framework\util;

use framework\core\http\Request;
use framework\core\http\Response;

class Url
{
    // url数据
    private $url;
    // url元素类型
    private static $types = ['scheme', 'host', 'port', 'path', 'query', 'fragment'];
    
    /*
     * url实例
     */
    public function __construct(array $url = [])
    {
        $this->url = $url;
    }
    
    /*
     * 当前页面url实例
     */
    public static function cur()
    {
        return self::parse(Request::server('REQUEST_URI'));
    }
    
    /*
     * 当前页面url实例
     */
    public static function full()
    {
        return self::parse(Request::url());
    }
    
    /*
     * 前一个页面url实例
     */
    public static function prev()
    {
        return self::parse(Request::server('HTTP_REFERER'));
    }
    
    /*
     * 解析url字符串返回实例
     */
    public static function parse($url)
    {
        $arr = parse_url($url);
        if (isset($arr['query'])) {
            parse_str($arr['query'], $arr['query']);
        }
        return new self($arr);
    }
    
    /*
     * 获取url元素值
     */
    public function __get($name)
    {
        return $this->get($name);
    }
    
    public function get($name)
    {
        if (in_array($name, self::$types)) {
            return $this->url[$name] ?? null;
        }
        throw new \Exception("Undefined url property: $$name");
    }
    
    /*
     * 设置url元素值
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }
    
    public function set($name, $value, $merge = false)
    {
        if (in_array($name, self::$types)) {
            if ($merge && $name === 'query' && isset($this->url[$name])) {
                $this->url[$name] = array_merge($this->url[$name], $value);
            } else {
                $this->url[$name] = $value;
            }
            return $this;
        }
        throw new \Exception("Undefined url property: $$name");
    }
    
    /*
     * 生成url字符串
     */
    public function make()
    {
        $ret = '';
        foreach (self::$types as $type) {
            if (isset($this->url[$type])) {
                $ret .= $this->build($type, $this->url[$type]);
            }
        }
        return $ret;
    }
    
    /*
     * url重定向
     */
    public function to($permanently = false)
    {
        Response::redirect($this->make(), $permanently);
    }
    
    /*
     * 返回url元素数组
     */
    public function toArray()
    {
        return $this->url;
    }

    /*
     * 魔术非方法返回url字符串
     */
    public function __toString()
    {
        return $this->make();
    }
    
    /*
     * 构建url元素字符串
     */
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
                return '/'.trim($value, '/');
            case 'query':
                return '?'.http_build_query($value);
            case 'fragment':
                return "#$value";
        }
    }
}
