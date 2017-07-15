<?php
use framework\App;
use framework\core\Error;
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

function db($name = null)
{
    return Container::load('db', $name);
}

function cache($name = null)
{
    return Container::load('cache', $name);
}

function storage($name = null)
{
    return Container::load('storage', $name);
}

function load($type, $name = null)
{
    return Container::load($type, $name);
}

function make($type = null, $name = null)
{
    return Container::make($type, $name);
}

function model($name, $config = null)
{
    return Container::model($name, $config);
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
