<?php
namespace framework\core\http;

use \framework\App;
use \framework\core\Hook;
use \framework\core\Logger;

class Response
{
    private static $response;
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$response) return;
        self::$response = new \stdClass();
        Hook::add('exit', __CLASS__.'::flush', null, 1);
    }
    
    public static function get($name, $default = null)
    {
        return isset(self::$response->$name) ? self::$response->$name : $default;
    }
    
    public static function set($name, $value)
    {
        self::$response->$name = $value;
    }
    
    public static function has($name, $key = null)
    {
        if ($key) {
            return isset(self::$response->$name);
        }
        return isset(self::$response->$name[$key]);
    }
    
    public static function status($code)
    {
        self::$response->status = $code;
    }
    
    public static function header($key, $value)
    {
        self::$response->headers[$key] = $value;
    }
    
    public static function headers(array $headers)
    {
        self::$response->headers = array_merge((array) self::$response->headers, $headers);
    }
    
    public static function wirte($body)
    {
        self::$response->body = isset(self::$response->body) ? self::$response->body.$body : $body;
    }
    
    public static function view($tpl, $vars)
    {
        self::$response->headers['Content-Type'] = 'text/html; charset=UTF-8';
        ob_start();
        \Framework\Core\View::render($tpl, $vars);
        self::$response->body = ob_get_clean();
        App::exit();
    }
    
    public static function json($data)
    {
        self::$response->headers['Content-Type'] = 'application/json; charset=UTF-8';
        self::$response->body = json_encode($data, JSON_UNESCAPED_UNICODE);
        App::exit();
    }
    
    public static function sendfile($file)
    {
        $file = realpath($file);
        self::$response->headers['X-Sendfile'] = $file;
        self::$response->headers['X-Accel-Redirect'] = $file;
        if (isset(self::$response->body)) self::$response->body = null;
        App::exit();
    }
    
    public static function redirect($url, $code = 302)
    {
        self::$response->status = ($code === 301) ? 301 : 302;
        self::$response->headers['Location'] = $url;
        if (isset(self::$response->body)) self::$response->body = null;
        App::exit();
    }
    
    public static function send($body, $type = null)
    {
        if ($type) {
            self::$response->headers['Content-Type'] = $type;
        }
        self::$response->body = $body;
        App::exit();
    }
    
    public static function clear($name = null)
    {
        if ($name) {
            if (isset(self::$response->$name)) {
                unset(self::$response->$name);
            }
        } else {
            self::$response = new \stdClass();
        }
    }
    
    public static function flush()
    {
        Hook::listen('response', self::$response);
        if (!headers_sent()) {
            if (isset(self::$response->status)) {
                http_response_code(self::$response->status);
            }
            if (isset(self::$response->headers)) {
                foreach (self::$response->headers as $hk => $hv) {
                    header($hk.': '.$hv);
                }
            }
        } else {
            Logger::write(Logger::WARNING, 'Response headers sent fail');
        }
        if (isset(self::$response->body)) {
            echo self::$response->body;
        }
        self::$response = null;
    }
}
Response::init();
