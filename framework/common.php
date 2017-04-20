<?php

use Framework\App;
use Framework\Core\Auth;
use Framework\Core\View;
use Framework\Core\Error;
use Framework\Core\Model;
use Framework\Core\Loader;
use Framework\Core\Logger;
use Framework\Core\Config;
use Framework\Core\Http\Request;
use Framework\Core\Http\Response;

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
    if (class_exists('Symfony\Component\VarDumper\VarDumper')) {
        foreach ($vars as $var) {
            Symfony\Component\VarDumper\VarDumper::dump($var);
        }
    } else {
        var_dump($vars);
    }
    App::exit();
}

function abort()
{
    App::exit();
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

function _include($file, array $params = null)
{
    if ($params !== null) {
        extract($vars, $params);
    }
    return include $file;
}
