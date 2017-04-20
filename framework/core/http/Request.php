<?php
namespace framework\core\http;

use framework\core\Hook;

class Request
{
    private static $request;
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$request) return;
        self::$request = new \stdClass();
        self::$request->env = $_ENV;
        self::$request->get = $_GET;
        self::$request->post = $_POST;
        self::$request->server = $_SERVER;
        Hook::add('exit', __CLASS__.'::clear');
        Hook::listen('request', self::$request);
    }
    
    public static function set($name, $key, $val = null)
    {
        if ($val === null) {
            self::$request->$name = $key;
        } else {
            self::$request->$name[$key] = $val;
        }
    }
    
    public static function has($name, $key = null)
    {
        if ($key == null) {
            return isset(self::$request->$name);
        }
        return isset(self::$request->$name[$key]);
    }
    
    public static function env($name = null, $default = null)
    {
        return isset(self::$request->env[$name]) ? self::$request->env[$name] : $default;
    }

    public static function get($name = null, $default = null)
    {
        return isset(self::$request->get[$name]) ? self::$request->get[$name] : $default;
    }
    
    public static function post($name = null, $default = null)
    {
        return isset(self::$request->post[$name]) ? self::$request->post[$name] : $default;
    }
    
    public static function params($name = null, $default = null)
    {
        return isset(self::$request->params[$name]) ? self::$request->params[$name] : $default;
    }
    
    public static function header($name = null, $default = null)
    {
        $name = 'HTTP_'.strtoupper($name);
        return isset(self::$request->server[$name]) ? self::$request->server[$name] : $default;
    }
    
    public static function server($name = null, $default = null)
    {
        return isset(self::$request->server[$name]) ? self::$request->server[$name] : $default;
    }
    
    public static function cookie($name = null, $default = null)
    {
        return Cookie::get($name, $default);
    }
    
    public static function session($name = null, $default = null)
    {
        return Session::get($name, $default);
    }
    
    public static function request($name = null, $default = null)
    {
        return isset(self::$request->request[$name]) ? self::$request->request[$name] : $default;
    }
    
    public static function dispatch($name, $default = null)
    {
        return isset(self::$request->dispatch[$name]) ? self::$request->dispatch[$name] : $default;
    }
    
    public static function url()
    {
        return isset(self::$request->url) ? self::$request->url : self::$request->url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }
    
    public static function host()
    {
        return isset(self::$request->host) ? self::$request->host : self::$request->host = $_SERVER['HTTP_HOST'];
    }
    
    public static function method()
    {
        return isset(self::$request->method) ? self::$request->method : self::$request->method = $_SERVER['REQUEST_METHOD'];
    }
    
    public static function ip($proxy = false)
    {
        if ($proxy) {
            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }
    
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
    
    public static function body()
    {
        return isset(self::$request->body) ? self::$request->body : self::$request->body = file_get_contents('php://input');
    }
    
    public static function agent()
    {
        return isset(self::$request->agent) ? self::$request->agent : self::$request->agent = new Agent(self::header('user-agent'));
    }
    
    public static function uploaded($name)
    {
        return isset($_FILES[$name]) ? new Uploaded($_FILES[$name]) : null;
    }
    
    public static function isPost()
    {
        return self::method() === 'POST';
    }
    
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public static function isPjax()
    {
        return self::is_ajax() && isset($_SERVER['HTTP_X_PJAX']);
    }

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
    
    public static function clear($name = null)
    {
        if ($name) {
            if (isset(self::$request->$name)) {
                unset(self::$request->$name);
            }
        } else {
            self::$request = null;
        }
    }
}
Request::init();
