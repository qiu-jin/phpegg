<?php
namespace app\library;

use framework\App;
use framework\core\View;
use framework\core\Loader;
use framework\core\http\Request;
use framework\core\http\Response;

class MyApp extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns' => 'controller',
    ];
    protected $con = 'app\controller\\';
    
    protected function dispatch()
    {
        // 从Url Query中获取请求控制器类与方法名
        $action = Request::get('a', 'index');
        $controller = Request::get('c', 'Home');
        $class = 'app\\'.$this->config['controller_ns'].'\\'.$controller;
        
        // 检查控制器类与方法是否合法
        if (substr($action, 0, 1) !== '_' && Loader::importPrefixClass($class)) {
            $controller_instance = new $class;
            // 检查控制器方法是否可用
            if (is_callable([$controller_instance, $action])) {
                return compact('action', 'controller', 'controller_instance');
            }
        }
        return false;
    }
    
    protected function call()
    {
        // 执行控制器方法
        return $this->dispatch['controller_instance']->{$this->dispatch['action']}();
    }
    
    protected function error($code = 500, $message = null)
    {
        // 输出视图error页面
        Response::send(View::error($code, $message), 'text/html; charset=UTF-8', false);
    }
    
    protected function response($return)
    {
        // 输出视图页面
        Response::view('/'.$this->dispatch['controller'].'/'.$this->dispatch['action'], $return);
    }
}

