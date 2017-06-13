<?php
use framework\App;
use framework\core\Auth;
use framework\core\Error;
use framework\core\Model;
use framework\core\Loader;
use framework\core\Logger;
use framework\core\Config;
use framework\core\Container;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\extend\view\Debug;

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

function container($name)
{
    return Container::get($name);
}

function db($name = null)
{
    return Container::connect('db', $name);
}

function cache($name = null)
{
    return Container::connect('cache', $name);
}

function storage($name = null)
{
    return Container::connect('storage', $name);
}

function connect($type, $name = null)
{
    return Container::connect($type, $name);
}

function load($type = null, $name = null)
{
    return Container::load($type, $name);
}

function dump(...$vars)
{
    Response::send(Debug::dump(...$vars));
}

function debug(...$vars)
{
    Response::send(Debug::render(...$vars));
}

function abort($code = null, $message = null)
{
    App::abort($code, $message);
}

function error($message, $limit = 1)
{
    return (bool) Error::set($message, E_USER_ERROR, $limit+1);
}

function driver($type, $driver, $config = [])
{
    $class = 'framework\driver\\'.$type.'\\'.ucfirst($driver);
    return new $class($config);
}

function jsonencode($data)
{
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

function jsondecode($data)
{
    return json_decode($data, true);
}

function __require($file)
{
    return require $file;
}
