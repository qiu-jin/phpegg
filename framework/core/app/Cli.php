<?php
namespace framework\core\app;

use framework\App;
use framework\core\Loader;

class Cli extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns' => 'command',
        // 控制器类名后缀
        'controller_suffix' => null,
    ];
    
    public function command($name, callback $call)
    {
        $this->dispatch[$name] = $call;
    }
    
    public function input()
    {

    }
    
    public function output()
    {

    }
    
    public function options($name, $default = null)
    {
        
    }
    
    protected function dispatch()
    {
        if (!App::IS_CLI) {
            throw new \RuntimeException('NOT CLI SAPI');
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
