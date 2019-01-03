<?php
namespace framework\util;

use framework\core\http\Client;
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
     * 获取url元素值魔术方法
     */
    public function __get($name)
    {
        return $this->get($name);
    }
    
	/*
	 * 获取url元素值
	 */
    public function get($name)
    {
        if (in_array($name, self::$types)) {
            return $this->url[$name] ?? null;
        }
        throw new \Exception("Undefined url property: $$name");
    }
	
	/*
	 * 获取query元素值
	 */
    public function getQuery($name = null)
    {
		return $name === null ? ($this->url['query'] ?? null) : ($this->url['query'][$name] ?? null);
    }
    
    /*
     * 设置url元素值魔术方法
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }
    
	/*
	 * 设置url元素值
	 */
    public function set($name, $value)
    {
        if (in_array($name, self::$types)) {
			$this->url[$name] = $value;
            return $this;
        }
        throw new \Exception("Undefined url property: $$name");
    }
	
	/*
	 * 设置query元素值
	 */
    public function setQuery($name, $value = null)
    {
		if (is_array($name)) {
			if ($value && isset($this->url['query'])) {
				$this->url['query'] = $name + $this->url['query'];
			} else {
				$this->url['query'] = $name;
			}
		} else {
			$this->url['query'][$name] = $value;
		}
		return $this;
    }
	
	/*
	 * 魔术方法
	 */
    public function __call($name, $params)
    {
		list($method, $type) = Str::cut(strtolower($name), 3);
		if (in_array($type, self::$types)) {
			switch ($method) {
				case 'get':
					return $this->get($type);
				case 'set':
					return $this->set($type, ...$params);
			}
		}
		throw new \BadMethodCallException("Undefined url method: $name");
    }
    
    /*
     * 生成url字符串
     */
    public function make()
    {
		$url = '';
		foreach (self::$types as $type) {
			if (isset($this->url[$type])) {
				$url .= $this->buildItem($type);
			}
		}
		return $url;
    }
    
    /*
     * url重定向
     */
    public function to($permanently = false)
    {
        Response::redirect($this->make(), $permanently);
    }

    /*
     * http请求
     */
    public function request($method = null)
    {
        return new Client($method, $this->make());
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
    private function buildItem($type)
    {
		$value = $this->url[$type];
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
