<?php
namespace framework\core\app;

use framework\App;
use framework\core\Command;

class Cli extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns' => 'command',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 默认调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
    ];
    
    public function command(...$params)
    {
        $count = count($params);
        if ($count === 1) {
            $this->dispatch[null] = $params[0];
            return $this
        } elseif ($count === 2) {
            $this->dispatch[$params[0]] = $params[1];
            return $this
        }
        throw new \RuntimeException('Command params error');
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
    
    protected function response($return = null) {}
}
