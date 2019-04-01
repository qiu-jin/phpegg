<?php
namespace framework\core\http;

use framework\core\Event;

class Request
{
    private static $init;
    private static $request;
    
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        self::$request = [
            'query'     => $_GET,
            'param'     => $_POST,
            'input'     => $_REQUEST,
            'server'    => $_SERVER,
        ];
        Event::trigger('request', self::$request);
    }
    
    /*
     * 获取GET值
     */
    public static function get($name = null, $default = null)
    {
        return self::query($name, $default);
    }
	
    /*
     * 获取GET值
     */
    public static function query($name = null, $default = null)
    {
        return $name === null ? self::$request['query'] : (self::$request['query'][$name] ?? $default);
    }
	
    /*
     * 获取GET值
     */
    public static function post($name = null, $default = null)
    {
        return self::param($name, $default);
    }
    
    /*
     * 获取POST值
     */
    public static function param($name = null, $default = null)
    {
        return $name === null ? self::$request['param'] : (self::$request['param'][$name] ?? $default);
    }
    
    /*
     * 获取REQUEST值
     */
    public static function input($name = null, $default = null)
    {
        return $name === null ? self::$request['input'] : (self::$request['input'][$name] ?? $default);
    }
    
    /*
     * 获取COOKIE值
     */
    public static function cookie($name = null, $default = null)
    {
        return $name === null ? Cookie::all() : Cookie::get($name, $default);
    }
    
    /*
     * 获取SESSION值
     */
    public static function session($name = null, $default = null)
    {
        return $name === null ? Session::all() : Session::get($name, $default);
    }
    
    /*
     * 获取FILES值
     */
    public static function file($name = null, $default = null)
    {
        return $name === null ? $_FILES : $_FILES[$name] ?? $default;
    }
    
    /*
     * 获取文件上传实例
     */
    public static function uploaded($name)
    {
		return new Uploaded($_FILES[$name]);
    }
    
    /*
     * 获取SERVER值
     */
    public static function server($name = null, $default = null)
    {
        return $name === null ? self::$request['server'] : self::$request['server'][$name] ?? $default;
    }
    
    /*
     * 获取HEADER值
     */
    public static function header($name, $default = null)
    {
        return self::$request['server']['HTTP_'.strtoupper(strtr($name, '-', '_'))] ?? $default;
    }
    
    /*
     * 获取当前url
     */
    public static function url()
    {
        return (self::isHttps() ? 'https' : 'http').'://'.self::host().$_SERVER['REQUEST_URI'];
    }
    
    /*
     * 获取host
     */
    public static function host()
    {
        return $_SERVER['HTTP_HOST'];
    }
    
    /*
     * 获取请求方法
     */
    public static function method()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }
    
    /*
     * 获取ip
     */
    public static function ip($proxy = false)
    {
        if (!$proxy) {
            return $_SERVER['REMOTE_ADDR'] ?? null;
        }
        return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    /*
     * 获取请求路径
     */
    public static function path()
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    
    /*
     * 获取请求路径数组
     */
    public static function pathArr()
    {
        $path = trim(self::path(), '/');
        return $path ? explode('/', $path) : [];
    }
    
    /*
     * 获取请求body内容
     */
    public static function body($cache = false)
    {
        return $cache ? (self::$request['body'] ?? self::$request['body'] = file_get_contents('php://input'))
                      : file_get_contents('php://input');
    }
    
    /*
     * 获取请求UserAgent实例
     */
    public static function agent($cache = false)
    {
        return $cache ? (self::$request['agent'] ?? self::$request['agent'] = new UserAgent($_SERVER['HTTP_USER_AGENT'])
                      : new UserAgent($_SERVER['HTTP_USER_AGENT'];
    }
    
    /*
     * 是否为POST请求
     */
    public static function isPost()
    {
        return self::method() === 'POST';
    }
    
    /*
     * 是否为Ajax请求
     */
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /*
     * 是否为Https请求
     */
    public static function isHttps($proxy = false)
    {
        if (!$proxy) {
            return isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on';
        }
        return isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
    }
    
    /*
     * 应用匿名函数处理内部数据
     */
    public static function apply(callable $call)
    {
        return $call(self::$request);
    }
    
    /*
     * 清理
     */
    public static function clean($name = null)
    {
        if ($name === null) {
            self::$request = null;
        } elseif (isset(self::$request[$name])) {
            unset(self::$request[$name]);
        }
    }
}
Request::__init();
