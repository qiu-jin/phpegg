<?php
namespace framework\core\http;

use framework\core\Hook;

class Request
{
    private static $request;
    
    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$request) return;
        self::$request = new \stdClass();
        self::$request->get =& $_GET;
        self::$request->post =& $_POST;
        Hook::add('exit', __CLASS__.'::free');
        Hook::listen('request', self::$request);
    }
    
    /*
     * 设置request值
     */
    public static function set($name, $key, $val = null)
    {
        if ($val === null) {
            self::$request->$name = $key;
        } else {
            self::$request->$name[$key] = $val;
        }
    }
    
    /*
     * 检查request值是否存在
     */
    public static function has($name, $key = null)
    {
        if ($key == null) {
            return isset(self::$request->$name);
        }
        return isset(self::$request->$name[$key]);
    }

    /*
     * 获取GET值
     */
    public static function get($name = null, $default = null)
    {
        if ($name === null) {
            return self::$request->get;
        }
        return isset(self::$request->get[$name]) ? self::$request->get[$name] : $default;
    }
    
    /*
     * 获取POST值
     */
    public static function post($name = null, $default = null)
    {
        if ($name === null) {
            return self::$request->post;
        }
        return isset(self::$request->post[$name]) ? self::$request->post[$name] : $default;
    }
    
    /*
     * 获取COOKIE值
     */
    public static function cookie($name = null, $default = null)
    {
        return Cookie::get($name, $default);
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
    public static function files($name = null, $default = null)
    {
        if ($name === null) {
            return $_FILES;
        }
        return isset($_FILES[$name]) ? $_FILES[$name] : $default;
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
        if ($name === null) {
            return $_SERVER;
        }
        return isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
    }
    
    /*
     * 获取HEADER值
     */
    public static function header($name, $default = null)
    {
        $name = 'HTTP_'.strtoupper(strtr('-', '_', $name));
        return isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
    }
    
    /*
     * 获取当前url
     */
    public static function url()
    {
        return isset(self::$request->url) ? self::$request->url : self::$request->url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }
    
    /*
     * 获取host
     */
    public static function host()
    {
        return isset(self::$request->host) ? self::$request->host : self::$request->host = $_SERVER['HTTP_HOST'];
    }
    
    /*
     * 获取请求方法
     */
    public static function method()
    {
        return isset(self::$request->method) ? self::$request->method : self::$request->method = $_SERVER['REQUEST_METHOD'];
    }
    
    /*
     * 获取语言
     */
    public static function lang()
    {
        return isset(self::$request->lang) ? self::$request->lang : self::$request->lang =  strtolower(strtok($_SERVER['HTTP_ACCEPT_LANGUAGE'], ','));
    }
    
    /*
     * 获取ip
     */
    public static function ip($proxy = false)
    {
        if ($proxy) {
            if (isset(self::$request->ip[1])) {
                return self::$request->ip[1];
            }
            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            } else {
                $ip = false;
            }
            return self::$request->ip[1] = $ip;
        } else {
            if (isset(self::$request->ip[0])) {
                return self::$request->ip[0];
            }
            return self::$request->ip[0] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
        }
    }
    
    /*
     * 获取请求路径
     */
    public static function path()
    {
        if (isset(self::$request->path)) {
            return self::$request->path;
        }
        if (isset($_GET['PATH_INFO'])) {
            return self::$request->path = trim($_GET['PATH_INFO'], '/');
        } elseif(isset($_SERVER['PATH_INFO'])) {
            return self::$request->path = trim($_SERVER['PATH_INFO'], '/');
        } else {
            return self::$request->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
    }
    
    /*
     * 获取请求body内容
     */
    public static function body()
    {
        return isset(self::$request->body) ? self::$request->body : self::$request->body = file_get_contents('php://input');
    }
    
    /*
     * 获取请求UserAgent实例
     */
    public static function agent()
    {
        return isset(self::$request->agent) ? self::$request->agent : self::$request->agent = new UserAgent($_SERVER['HTTP_USER_AGENT']);
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
     * 是否为Pjax请求
     */
    public static function isPjax()
    {
        return self::isAjax() && isset($_SERVER['HTTP_X_PJAX']);
    }

    /*
     * 是否为Https请求
     */
    public static function isHttps()
    {
    	if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
    		return true;
    	} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    		return true;
    	} elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
    		return true;
    	}
    	return false;
    }
    
    /*
     * 清理资源
     */
    public static function free()
    {
        self::$request = null;
    }
}
Request::init();
