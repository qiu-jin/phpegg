<?php
use framework\App;
use framework\core\Error;
use framework\core\Getter;
use framework\core\Logger;
use framework\core\Config;
use framework\core\Container;
use framework\core\Validator;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\extend\debug\Debug;

function env($name, $default = null)
{
    return Config::env($name, $default);
}

function config($name, $default = null)
{
    return Config::get($name, $default);
}

function logger($name = null)
{
    return Logger::channel($name);
}

function make($name)
{
    return Container::make($name);
}

function db($name = null)
{
    return Container::driver('db', $name);
}

function rpc($name = null)
{
    return Container::driver('rpc', $name);
}

function cache($name = null)
{
    return Container::driver('cache', $name);
}

function storage($name = null)
{
    return Container::driver('storage', $name);
}

function sms($name = null)
{
    return Container::driver('sms', $name);
}

function email($name = null)
{
    return Container::driver('email', $name);
}

function job($name, $message)
{
    return Container::driver($name)->producer()->push($message);
}

function driver($type, $name = null)
{
    return Container::driver($type, $name);
}

function model($name)
{
    return Container::model($name);
}

function view($path, array $vars = null)
{
    return Response::view($path, $vars);
}

function validate($rule, $message = null)
{
    return new Validator($rule, $message);
}

function dd(...$vars)
{
    Response::send(Debug::dump(...$vars));
}

function dump(...$vars)
{
    Response::send(Debug::dump(...$vars), false);
}

function abort($code = null, $message = null)
{
    App::abort($code, $message);
}

function error($message, $limit = 1)
{
    Error::trigger($message, E_USER_ERROR, $limit + 1);
}

function instance($class, ...$params)
{
    return new $class(...$params);
}

function input($name, ...$params)
{
    return Request::$name(...$params);
}

function output($name, ...$params)
{
    return $params ? Response::$name(...$params) : Response::send($name); 
}

function jsonencode($data)
{
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

function jsondecode($data)
{
    return json_decode($data, true);
}

function __include($file)
{
    return include $file;
}

function __require($file)
{
    return require $file;
}

function closure_bind_getter(Closure $call, $providers = null)
{
    return Closure::bind($call, new class ($providers) {
        use Getter;
        public function __construct($providers) {
            if ($providers) {
                $this->{Config::env('GETTER_PROVIDERS_NAME')} = $providers;
            }
        }
    });
}

define('OPCACHE_LOADED', extension_loaded('opcache'));

function is_php_file($file)
{
    return (OPCACHE_LOADED && opcache_is_script_cached($file)) || is_file($file);
}
