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

function config($name, $default = null)
{
    return Config::get($name, $default);
}

function import($name, $ignore = true, $cache = false)
{
    return Loader::import($name, $ignore, $cache);
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

function driver($type, $driver, $config = [])
{
    $class = 'framework\driver\\'.$type.'\\'.ucfirst($driver);
    return new $class($config);
}

function dump(...$vars)
{
    ob_start();
    if (class_exists('Symfony\Component\VarDumper\VarDumper')) {
        foreach ($vars as $var) {
            Symfony\Component\VarDumper\VarDumper::dump($var);
        }
    } else {
        var_dump($vars);
    }
    Response::send(ob_get_clean());
}

function abort()
{
    App::abort();
}

function error($message)
{
    
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
