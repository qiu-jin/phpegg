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
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        self::$request = [
            'query'     => &$_GET,
            'content'   => &$_POST,
            'server'    => &$_SERVER,
            'params'    => &$_REQUEST
        ];
        Event::on('exit', __CLASS__.'::clean');
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
     * 获取POST值
     */
    public static function post($name = null, $default = null)
    {
        return self::content($name, $default);
    }
    
    /*
     * 获取GET值
     */
    public static function query($name = null, $default = null)
    {
        return $name === null ? self::$request['query'] : (self::$request['query'][$name] ?? $default);
    }
    
    /*
     * 获取POST值
     */
    public static function content($name = null, $default = null)
    {
        return $name === null ? self::$request['content'] : (self::$request['content'][$name] ?? $default);
    }
    
    /*
     * 获取COOKIE值
     */
    public static function cookie($name = null, $default = null)
    {
        return Cookie::get($name, $default);
    }
    
    /*
     * 获取param
     */
    public static function param($name = null, $default = null)
    {
       return $name === null ? self::$request['params'] : (self::$request['params'][$name] ?? $default);
    }
    
    /*
     * 获取SESSION值
     */
    public static function session($name = null, $default = null)
    {
        return Session::get($name, $default);
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
    public static function uploaded($name, $validate = null)
    {
        if (isset($_FILES[$name])) {
            if (is_array($_FILES[$name]['name'])) {
                $keys = array_keys($_FILES[$name]);
                $count = count($_FILES[$name]['name']);
                for ($i = 0; $i < $count; $i++) {
                    $files[] = new Uploaded(array_combine($keys, array_column($_FILES[$name], $i)), $validate);
                }
                return $files;
            } else {
                return new Uploaded($_FILES[$name], $validate);
            }
        }
        return null;
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
        return self::$request['server']['HTTP_'.strtoupper(strtr('-', '_', $name))] ?? $default;
    }
    
    /*
     * 获取当前url
     */
    public static function url()
    {
        return self::$request['url'] ?? self::$request['url'] = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }
    
    /*
     * 获取host
     */
    public static function host()
    {
        return self::$request['host'] ?? self::$request['host'] = $_SERVER['HTTP_HOST'];
    }
    
    /*
     * 获取请求方法
     */
    public static function method()
    {
        return self::$request['method'] ?? self::$request['method'] = $_SERVER['REQUEST_METHOD'];
    }
    
    /*
     * 获取ip
     */
    public static function ip($proxy = false)
    {
        if (!$proxy) {
            return self::$request['ip'][0] ?? self::$request['ip'][0] = $_SERVER['REMOTE_ADDR'] ?? false;
        }
        return self::$request['ip'][1] ?? self::$request['ip'][1] = (
            $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
                                       ?? $_SERVER['REMOTE_ADDR'] 
                                       ?? false
        );
    }
    
    /*
     * 获取请求路径
     */
    public static function path()
    {
        return self::$request['path'] ?? self::$request['path'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    
    /*
     * 获取请求路径数组
     */
    public static function pathArr()
    {
        $path = trim(self::path(), '/');
        return empty($path) ? null : explode('/', $path);
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
    public static function agent($str = null)
    {
        return new UserAgent($str ?? $_SERVER['HTTP_USER_AGENT']);
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
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') {
            return true;
        }
        return $proxy && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
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
Request::init();
