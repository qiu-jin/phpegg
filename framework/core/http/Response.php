<?php
namespace framework\core\http;

use \framework\App;
use \framework\core\Hook;
use \framework\core\View;
use \framework\core\Error;

class Response
{
    private static $response;
    
    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$response) return;
        self::$response = new \stdClass();
        Hook::add('exit', __CLASS__.'::flush', null, 1);
    }
    
    /*
     * 获取response值
     */
    public static function get($name, $default = null)
    {
        return isset(self::$response->$name) ? self::$response->$name : $default;
    }
    
    /*
     * 检查response值是否存在
     */
    public static function has($name, $key = null)
    {
        if ($key) {
            return isset(self::$response->$name);
        }
        return isset(self::$response->$name[$key]);
    }
    
    /*
     * 设置响应状态码
     */
    public static function status($code)
    {
        self::$response->status = $code;
    }
    
    /*
     * 设置响应单个header头
     */
    public static function header($key, $value)
    {
        self::$response->headers[$key] = $value;
    }
    
    /*
     * 设置响应多个header头
     */
    public static function headers(array $headers)
    {
        self::$response->headers = array_merge((array) self::$response->headers, $headers);
    }
    
    /*
     * 设置响应cookie
     */
    public static function cookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        Cookie::set($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    
    /*
     * 设置响应body内容，追加写入
     */
    public static function wirte($body)
    {
        self::$response->body = isset(self::$response->body) ? self::$response->body.$body : $body;
    }
    
    /*
     * 设置视图响应
     */
    public static function view($tpl, $vars = null)
    {
        self::$response->headers['Content-Type'] = 'text/html; charset=UTF-8';
        self::$response->body = View::render($tpl, $vars);
        App::exit();
    }
    
    /*
     * 设置响应json格式化数据
     */
    public static function json($data)
    {
        self::$response->headers['Content-Type'] = 'application/json; charset=UTF-8';
        self::$response->body = json_encode($data, JSON_UNESCAPED_UNICODE);
        App::exit();
    }
    
    /*
     * 设置响应重定向
     */
    public static function redirect($url, $code = 302)
    {
        self::$response->status = ($code === 301) ? 301 : 302;
        self::$response->headers['Location'] = $url;
        if (isset(self::$response->body)) self::$response->body = null;
        App::exit();
    }
    
    /*
     * 设置响应body内容
     */
    public static function send($body, $type = null)
    {
        if ($type) {
            self::$response->headers['Content-Type'] = $type;
        }
        self::$response->body = $body;
        App::exit();
    }
    
    /*
     * 清除响应值
     */
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
    
    /*
     * 输出响应
     */
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
            Error::set('Response headers sent fail');
        }
        if (isset(self::$response->body)) {
            echo self::$response->body;
        }
        self::$response = null;
    }
}
Response::init();
