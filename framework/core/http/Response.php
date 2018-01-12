<?php
namespace framework\core\http;

use framework\App;
use framework\core\View;
use framework\core\Event;

class Response
{
    private static $init;
    private static $response;
    
    /*
     * 初始化
     */
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        self::$response = new \stdClass();
        Event::on('flush', __CLASS__.'::flush');
    }
    
    /*
     * 获取response值
     */
    public static function get($name, $default = null)
    {
        return self::$response->$name ?? $default;
    }
    
    /*
     * 检查response值是否存在
     */
    public static function has($name, $key = null)
    {
        return $key === null ? isset(self::$response->$name) : isset(self::$response->$name[$key]);
    }
    
    /*
     * 设置响应状态码
     */
    public static function status($code = 200)
    {
        self::$response->status = isset(Status::CODE[$code]) ? $code : 500;
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
        if (isset(self::$response->headers)) {
            self::$response->headers = $headers + self::$response->headers;
        } else {
            self::$response->headers = $headers;
        }
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
    public static function view($tpl, $vars = null, $exit = true)
    {
        self::$response->headers['Content-Type'] = 'text/html; charset=UTF-8';
        self::$response->body = View::render($tpl, $vars);
        if ($exit) App::exit();
    }
    
    /*
     * 设置响应json格式化数据
     */
    public static function json($data, $exit = true)
    {
        self::$response->headers['Content-Type'] = 'application/json; charset=UTF-8';
        self::$response->body = jsonencode($data);
        if ($exit) App::exit();
    }
    
    /*
     * 设置响应重定向
     */
    public static function redirect($url, $permanently = false, $exit = true)
    {
        self::$response->status = $permanently ? 301 : 302;
        self::$response->headers['Location'] = $url;
        if (isset(self::$response->body)) self::$response->body = null;
        if ($exit) App::exit();
    }
    
    /*
     * 设置响应body内容
     */
    public static function send($body, $type = null, $exit = true)
    {
        if ($type) {
            self::$response->headers['Content-Type'] = $type;
        }
        self::$response->body = $body;
        if ($exit) App::exit();
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
        Event::trigger('response', self::$response);
        if (headers_sent()) {
            throw new \Exception('Response headers sent failure');
        }
        if (isset(self::$response->status)) {
            http_response_code(self::$response->status);
        }
        if (isset(self::$response->headers)) {
            foreach (self::$response->headers as $k => $v) {
                header("$k: $v");
            }
        }
        if (isset(self::$response->body)) {
            echo self::$response->body;
        }
        self::$response = null;
    }
}
Response::init();
