<?php
use framework\App;
use framework\util\Url;
use framework\util\Date;
use framework\core\Debug;
use framework\core\Error;
use framework\core\Event;
use framework\core\Getter;
use framework\core\Logger;
use framework\core\Config;
use framework\core\Container;
use framework\core\Validator;
use framework\core\http\Client;
use framework\core\http\Cookie;
use framework\core\http\Session;
use framework\core\http\Request;
use framework\core\http\Response;

/*
 * 获取环境设置
 */
function env($name, $default = null)
{
    return Config::env($name, $default);
}

/*
 * 获取配置设置
 */
function config($name, $default = null)
{
    return Config::get($name, $default);
}

/*
 * 获取容器实例
 */
function app($name = null)
{
    return isset($name) ? Container::make($name) : App::instance();
}

/*
 * 获取日志实例
 */
function logger($name = null, $use_null_handler = false)
{
    return Logger::channel($name, $use_null_handler);
}

/*
 * 获取驱动实例
 */
function driver($type, $name = null)
{
    return Container::driver($type, $name);
}

/*
 * 获取数据库实例
 */
function db($name = null)
{
    return Container::driver('db', $name);
}

/*
 * 获取缓存实例
 */
function cache($name = null)
{
    return Container::driver('cache', $name);
}

/*
 * 发送EMAIL或获取EMAIL实例
 */
function email($param, ...$params)
{
	return $params ? Container::driver('email')->send($param, ...$params) : Container::driver('email', $param);
}

/*
 * 设置事件
 */
function event($name, callable $call, $priority = 0)
{
    Event::on($name, $call, $priority);
}

/*
 * 输出视图页面
 */
function view($tpl, array $vars = null)
{
    return Response::view($tpl, $vars);
}

/*
 * 获取请求参数
 */
function input($name = null, $default = null)
{
    return Request::input($name, $default);
}

/*
 * 设置响应内容
 */
function output($name, $type = null)
{
    return Response::send($name, $type); 
}

/*
 * 获取或设置COOKIE
 */
function cookie($name, ...$params)
{
    return $params ? Cookie::set($name, ...$params) : Cookie::get($name); 
}

/*
 * 获取或设置SESSION
 */
function session($name, $value = null)
{
	return isset($value) ? Session::set($name, $value) : Session::get($name);
}

/*
 * 验证器
 */
function validate($rule, $message = null)
{
    return new Validator($rule, $message);
}

/*
 * HTTP请求实例
 */
function request($method, $url)
{
    return new Client($method, $url);
}

/*
 * 中断应用
 */
function abort($code = null, $message = null)
{
    App::abort($code, $message);
}

/*
 * 设置警告
 */
function warn($message, $limit = 1)
{
    Error::trigger($message, E_USER_WARNING, $limit + 1);
	return false;
}

/*
 * 设置错误
 */
function error($message, $limit = 1)
{
    Error::trigger($message, E_USER_ERROR, $limit + 1);
	return false;
}

/*
 * 调试
 */
function dd(...$vars)
{
    Response::send(Debug::dump(...$vars));
}

/*
 * 调试
 */
function dump(...$vars)
{
    Response::send(Debug::dump(...$vars), false);
}

/*
 * 类实例化
 */
function instance($class, ...$params)
{
    return new $class(...$params);
}

/*
 * JSON序列化
 */
function jsonencode($data)
{
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

/*
 * JSON反序列化
 */
function jsondecode($data)
{
    return json_decode($data, true);
}

/*
 * 获取Getter
 */
function getter($providers = null)
{
    return new class ($providers) {
        use Getter;
        public function __construct($providers) {
            $this->{Config::get('container.getter_providers_name')} = $providers;
        }
    };
}

/*
 * 检查文件存在
 */
define('OPCACHE_LOADED', extension_loaded('opcache'));

function is_php_file($file)
{
    return (OPCACHE_LOADED && opcache_is_script_cached($file)) || is_file($file);
}

/*
 * 安全引用文件
 */
function __require($file)
{
    return require $file;
}
