<?php
use framework\App;
use framework\core\Error;
use framework\core\Logger;
use framework\core\Config;
use framework\core\Container;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\extend\view\Debug;

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

function driver($type, $name = null)
{
    return Container::driver($type, $name);
}

function model($name)
{
    return Container::model($name);
}

function input($name, ...$params)
{
    return Request::$name(...$params);
}

function output($name, ...$params)
{
    return $params ? Response::$name(...$params) : Response::send($name); 
}

function view($tpl, array $vars = null)
{
    return Response::view($tpl, $vars);
}

function dd(...$vars)
{
    Response::send(Debug::dump(...$vars));
}

function abort($code = null, $message = null)
{
    App::abort($code, $message);
}

function error($message, $limit = 1)
{
    return (bool) Error::set($message, E_USER_ERROR, $limit+1);
}

function jsonencode($data)
{
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

function jsondecode($data)
{
    return json_decode($data, true);
}

function is_php_file($file)
{
    return (function_exists('opcache_is_script_cached') && opcache_is_script_cached($file)) || is_file($file);
}

function __include($file)
{
    return include $file;
}

function __require($file)
{
    return require $file;
}
