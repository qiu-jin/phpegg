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
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        Event::on('flush', [__CLASS__, 'flush']);
    }
    
    /*
     * 设置响应状态码
     */
    public static function status($code = 200)
    {
        self::$response['status'] = isset(Status::CODE[$code]) ? $code : 200;
    }
    
    /*
     * 设置响应单个header头
     */
    public static function header($name, $value)
    {
        self::$response['headers'][$name] = $value;
    }
    
    /*
     * 设置响应多个header头
     */
    public static function headers(array $headers)
    {
        if (isset(self::$response['headers'])) {
            self::$response['headers'] = $headers + self::$response['headers'];
        } else {
            self::$response['headers'] = $headers;
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
        self::$response['body'] = isset(self::$response['body']) ? self::$response['body'].$body : $body;
    }
    
    /*
     * 设置响应html
     */
    public static function html($html)
    {
        self::send($html, 'text/html; charset=UTF-8');
    }
    
    /*
     * 设置视图响应
     */
    public static function view($tpl, $vars = null)
    {
        self::send(View::render($tpl, $vars), 'text/html; charset=UTF-8');
    }
    
    /*
     * 设置响应json格式化数据
     */
    public static function json($data)
    {
        self::send(jsonencode($data), 'application/json; charset=UTF-8');
    }
    
    /*
     * 设置响应body内容
     */
    public static function send($body, $type = null)
    {
        self::$response['body'] = $body;
        if ($type) {
            self::$response['headers']['Content-Type'] = $type;
        }
        App::exit();
    }
    
    /*
     * 设置响应重定向
     */
    public static function redirect($url, $permanently = false, $exit = true)
    {
        self::$response['status'] = $permanently ? 301 : 302;
        self::$response['headers']['Location'] = $url;
        self::$response['body'] = null;
        App::exit();
    }
    
    /*
     * 处理response
     */
    public static function apply(callable $call)
    {
        return $call(self::$response);
    }
    
    /*
     * 清除响应值
     */
    public static function clean($name = null)
    {
        if ($name === null) {
            self::$response = null;
        } elseif (isset(self::$response[$name])) {
            unset(self::$response[$name]);
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
        if (isset(self::$response['status'])) {
            http_response_code(self::$response['status']);
        }
        if (isset(self::$response['headers'])) {
            foreach (self::$response['headers'] as $k => $v) {
                header("$k: $v");
            }
        }
        if (isset(self::$response['body'])) {
            echo self::$response['body'];
        }
        self::$response = null;
    }
}
Response::__init();
