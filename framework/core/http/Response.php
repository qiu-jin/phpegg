<?php
namespace framework\core\http;

use framework\App;
use framework\util\File;
use framework\core\View;
use framework\core\Event;

class Response
{
    private static $init;
	// 响应内容
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
        Event::on('app.flush', [__CLASS__, 'flush']);
    }
    
    /*
     * 设置响应状态码
     */
    public static function code($code = 200)
    {
		self::$response->code = $code;
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
    	$name, $value, $lifetime = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null
    ) {
        self::$response->cookies[$name] = func_get_args();
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
        self::send(json_encode($data, JSON_UNESCAPED_UNICODE), 'application/json; charset=UTF-8');
    }
	
    /*
     * 设置文件输出
     */
    public static function file($file, $type = null)
    {
		self::$response->body = null;
		self::$response->headers['Content-Length'] = filesize($file);
		self::$response->headers['Content-Type'] = $type ?? File::mime($file);
		self::flush();
		readfile($file);
		App::exit();
    }
    
    /*
     * 设置响应body内容
     */
    public static function send($body = null, $type = null)
    {
        if (isset($body)) {
            self::$response->body = $body;
        }
        if (isset($type)) {
            self::$response->headers['Content-Type'] = $type;
        }
        App::exit();
    }
    
    /*
     * 设置响应重定向
     */
    public static function redirect($url, $permanently = false)
    {
        self::$response->code = $permanently ? 301 : 302;
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
     * 输出响应
     */
    public static function flush()
    {
        if (isset(self::$response->code)) {
            http_response_code(self::$response->code);
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
