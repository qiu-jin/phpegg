<?php

use framework\App;
use framework\core\Auth;
use framework\core\Error;
use framework\core\Model;
use framework\core\Loader;
use framework\core\Logger;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\extend\view\Dumper;

function auth()
{
    
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

function env($name, $default = null)
{
    return Config::getEnv($name, $default);
}

function config($name, $default = null)
{
    return Config::get($name, $default);
}

function logger($name = null)
{
    return Logger::channel($name);
}

function model($name)
{
    return Model::get($name);
}

function db($name = null)
{
    return Model::connect('db', $name);
}

function cache($name = null)
{
    return Model::connect('cache', $name);
}

function storage($name = null)
{
    return Model::connect('storage', $name);
}

function connect($type, $name = null)
{
    return Model::connect($type, $name);
}

function load($type = null, $name = null)
{
    return App::load($type, $name);
}

function dump(...$vars)
{
    Response::send(Dumper::dump(...$vars));
}

function debug(...$vars)
{
    //Response::send(Debug::render(...$vars));
}

function abort($code = null, $message = null)
{
    App::abort($code, $message);
}

function error($message, $code = E_USER_ERROR, $limit = 1)
{
    Error::set($message, $code, $limit+1);
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
