<?php
use framework\App;
use framework\util\Url;
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
use framework\extend\debug\Debug;

define('OPCACHE_LOADED', extension_loaded('opcache'));

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
    return $name === null ? App::instance() : Container::get($name);
}

/*
 * 获取日志实例
 */
function logger($name = null)
{
    return Logger::channel($name);
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
 * 获取RPC实例
 */
function rpc($name = null)
{
    return Container::driver('rpc', $name);
}

/*
 * 获取缓存实例
 */
function cache($name = null)
{
    return Container::driver('cache', $name);
}

/*
 * 获取存储实例
 */
function storage($name = null)
{
    return Container::driver('storage', $name);
}

/*
 * 发送短信或获取短信实例
 */
function sms(...$params)
{
    return isset($params[1]) ? Container::driver('sms')->send(...$params) : Container::driver('sms', ...$params);
}

/*
 * 发送EMAIL或获取EMAIL实例
 */
function email(...$params)
{
	return isset($params[1]) ? Container::driver('email')->send(...$params) : Container::driver('email', ...$params);
}

/*
 * 设置事件
 */
function event($name, callable $call, $priority = 0)
{
    Event::on($name, $call, $priority);
}

/*
 * 发送队列任务
 */
function job($name, $message, $driver = null)
{
    return Container::driver('queue', $driver)->producer($name)->push($message);
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
function input($name, $default = null)
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
	return is_array($name) || $value !== null ? Session::set($name, $value) : Session::get($name);
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
function request($url, $method = null)
{
    return new Client($method, $url);
}

/*
 * URL实例
 */
function url($url)
{
    return is_array($url) ? new Url($url) : Url::parse($url);
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
}

/*
 * 设置错误
 */
function error($message, $limit = 1)
{
    Error::trigger($message, E_USER_ERROR, $limit + 1);
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
 * 安全引用文件
 */
function __include($file)
{
    return include $file;
}

/*
 * 安全引用文件
 */
function __require($file)
{
    return require $file;
}

/*
 * 获取Getter
 */
function getter($providers = null)
{
    return new class ($providers) {
        use Getter;
        public function __construct($providers) {
            if ($providers) {
                $this->{app\env\GETTER_PROVIDERS_NAME} = $providers;
            }
        }
    };
}

/*
 * 检查文件存在
 */
function is_php_file($file)
{
    return (OPCACHE_LOADED && opcache_is_script_cached($file)) || is_file($file);
}
