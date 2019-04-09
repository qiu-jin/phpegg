<?php
namespace framework\core\http;

use framework\core\Event;
use framework\core\Config;

class Request
{
    private static $init;
    // 是否代理请求
	private static $proxy;
	
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
		self::$proxy = Config::env('HTTP_REQUEST_PROXY');
        Event::trigger('request');
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
        return $name === null ? $_GET : ($_GET[$name] ?? $default);
    }
	
    /*
     * 获取POST值
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
        return $name === null ? $_POST : ($_POST[$name] ?? $default);
    }
    
    /*
     * 获取REQUEST值
     */
    public static function input($name = null, $default = null)
    {
        return $name === null ? $_REQUEST : ($_REQUEST[$name] ?? $default);
    }
    
    /*
     * 获取SERVER值
     */
    public static function server($name = null, $default = null)
    {
        return $name === null ? $_SERVER : ($_SERVER[$name] ?? $default);
    }
    
    /*
     * 获取HEADER值
     */
    public static function header($name, $default = null)
    {
        return $_SERVER['HTTP_'.strtoupper(strtr($name, '-', '_'))] ?? $default;
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
    public static function file($name = null)
    {
        return $name === null ? $_FILES : $_FILES[$name] ?? null;
    }
    
    /*
     * 获取文件上传实例
     */
    public static function uploaded($name)
    {
        if (isset($_FILES[$name])) {
            if (is_array($_FILES[$name]['name'])) {
                $keys = array_keys($_FILES[$name]);
                $count = count($_FILES[$name]['name']);
                for ($i = 0; $i < $count; $i++) {
                    $files[] = new Uploaded(array_combine($keys, array_column($_FILES[$name], $i)));
                }
                return $files;
            } else {
                return new Uploaded($_FILES[$name]);
            }
        }
    }
    
    /*
     * 获取host
     */
    public static function host()
    {
        return $_SERVER['HTTP_HOST'];
    }
	
    /*
     * 获取当前uri
     */
    public static function uri()
    {
        return $_SERVER['REQUEST_URI'];
    }
	
    /*
     * 获取当前url
     */
    public static function url()
    {
        return (self::isHttps() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
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
    public static function ip()
    {
        if (!self::$proxy) {
            return $_SERVER['REMOTE_ADDR'] ?? null;
        }
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
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
    public static function body()
    {
        return file_get_contents('php://input');
    }
    
    /*
     * 获取请求UserAgent实例
     */
    public static function agent()
    {
        return new UserAgent($_SERVER['HTTP_USER_AGENT']);
    }
    
    /*
     * 是否为POST请求
     */
    public static function isPost()
    {
        return self::method() == 'POST';
    }
    
    /*
     * 是否为Ajax请求
     */
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
			   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /*
     * 是否为Https请求
     */
    public static function isHttps()
    {
        if (!self::$proxy) {
            return isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';
        }
        return isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';
    }    
}
Request::__init();
