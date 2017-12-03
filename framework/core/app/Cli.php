<?php
namespace framework\core\app;

use framework\App;

class Cli extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns' => 'command',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 路由模式下是否启用Getter魔术方法
        'route_dispatch_enable_getter' => true,
    ];
    // 核心错误
    protected $core_errors = [
        404 => '',
        500 => ''
    ];
    
    public function input()
    {

    }
    
    public function output()
    {

    }
    
    public function command($name, callback $call)
    {
        $this->dispatch[$name] = $call;
        
        $app->command($name, function () {
            
        })
    }
    
    protected function dispatch()
    {
        if (PHP_SAPI !== 'cli') {
            throw new \RuntimeException('NO CLI SAPI');
        }
    }
    
    protected function call()
    {
        
    }
    
    protected function error($code = null, $message = null)
    {
        
    }
    
    protected function response($return = null)
    {

    }
}

function input(...$params)
{
    App::instance()->input(...$params);
}

function output(...$params)
{
    App::instance()->output(...$params);
}
