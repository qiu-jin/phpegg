<?php
namespace app\library;

use framework\App;
use framework\core\View;
use framework\core\Loader;
use framework\core\http\Request;
use framework\core\http\Response;

class MyApp extends App
{
    protected $ns = 'app\controller\\';
    
    protected function dispatch()
    {
        $action = Request::get('action');
        $controller = Request::get('controller');
        if ($action && $controller && $action[0] !== '_' && Loader::importPrefixClass($this->ns.$controller)) {
            $instance = new $this->ns.$controller;
            if (is_callable([$instance, $action])) {
                return compact('action', 'controller', 'instance');
            }
        }
        return false;
    }
    
    protected function call()
    {
        return $this->dispatch['instance']->{$this->dispatch['action']}();
    }
    
    protected function error($code = 500, $message = null)
    {
        View::error($code, $message);
    }
    
    protected function response($return)
    {
        Response::view('/'.$this->dispatch['controller'].'/'.$this->dispatch['action'], $return);
    }
}

