<?php
namespace framework\core\http;

use framework\App;
use framework\util\File;
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
		self::$response = new \stdClass();
        Event::on('flush', [__CLASS__, 'flush']);
    }
    
    /*
     * 设置响应状态码
     */
    public static function status($code = 200)
    {
		self::$response->status = $code;
    }
    
    /*
     * 设置响应单个header头
     */
    public static function header($name, $value)
    {
        self::$response->headers[$name] = $value;
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
    public static function cookie(
    	$name, $value, $lifetime = null, $path = null, $domain = null, $secure = null, $httponly = null
    ) {
        self::$response->cookies[] = func_get_args();
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
        self::html(View::render($tpl, $vars));
    }
    
    /*
     * 设置响应html
     */
    public static function html($html)
    {
        self::send($html, 'text/html; charset=UTF-8');
    }
    
    /*
     * 设置响应json格式化数据
     */
    public static function json($data)
    {
        self::send(jsonencode($data), 'application/json; charset=UTF-8');
    }
	
    /*
     * 设置文件输出
     */
    public static function file($path, $type = null)
    {
		self::$response->body = null;
		self::$response->headers['Content-Length'] = filesize($file);
		self::$response->headers['Content-Type'] = $type ?? File::mime($path);
		self::flush();
		readfile($path);
		App::exit();
    }
    
    /*
     * 设置响应body内容
     */
    public static function send($body, $type = null)
    {
        self::$response->body = $body;
        if ($type) {
            self::$response->headers['Content-Type'] = $type;
        }
        App::exit();
    }
    
    /*
     * 设置响应重定向
     */
    public static function redirect($url, $permanently = false)
    {
        self::$response->status = $permanently ? 301 : 302;
        self::$response->headers['Location'] = $url;
        self::$response->body = null;
        App::exit();
    }
	
    /*
     * 设置文件下载
     */
    public static function download($file, $name = null, $is_buffer = false)
    {
		if (!$is_buffer) {
			self::$response->headers['Content-Disposition'] = 'attachment; filename="'.($name ?? basename($file)).'"';
			return self::file($file);
		} else {
			self::$response->headers['Content-Length'] = strlen($file);
			self::$response->headers['Content-Disposition'] = 'attachment; filename="'.$name.'"';
			return self::send($file, File::mime($file, true) ?: 'application/octet-stream');
		}
    }
    
    /*
     * 应用匿名函数处理内部数据
     */
    public static function apply(callable $call)
    {
        return $call(self::$response);
    }
    
    /*
     * 输出响应
     */
    public static function flush()
    {
        if (headers_sent()) {
            throw new \Exception('Response headers sent failure');
        }
        Event::trigger('response', self::$response);
        if (isset(self::$response->status)) {
            http_response_code(self::$response->status);
        }
        if (isset(self::$response->headers)) {
            foreach (self::$response->headers as $k => $v) {
                header("$k: $v");
            }
        }
		if (isset(self::$response->cookies)) {
			foreach (self::$response->cookies as $v) {
				Cookie::setCookie(...$v);
			}
		}
        if (isset(self::$response->body)) {
            echo self::$response->body;
        }
        self::$response = null;
    }
}
Response::__init();
