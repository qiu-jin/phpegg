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
        // options to params
        'options_to_params' => false,
        // 是否启用readline
        'enable_readline'   => false,
        // 默认命令
        'default_commands' => null,
        // 匿名函数是否启用Getter魔术方法
        'enable_closure_getter' => true,
    ];
    protected $arguments;
    protected $is_single_command = false;

    public function command(...$params)
    {
        $count = count($params);
        if ($count === 1) {
            if (is_array($params[0])) {
                $this->dispatch = $params[0] + $this->dispatch;
            } else {
                $this->dispatch = $params[0];
                $this->is_single_command = true;
            }
            return $this;
        } elseif ($count === 2) {
            $this->dispatch[$params[0]] = $params[1];
            return $this;
        }
        throw new \RuntimeException('Command params error');
    }
    
    protected function dispatch()
    {
        if (PHP_SAPI === 'cli') {
            defined('IS_CLI', true);
        } else {
            throw new \RuntimeException('NOT CLI SAPI');
        }
        $this->parseArgv();
        return $this->config['default_commands'] ?: [];
    }
    
    protected function call()
    {
        $name = '';
        if ($this->is_single_command) {
            $call = $this->dispatch;
        } elseif (isset($this->dispatch[$name])) {
            $call = $this->dispatch[$name];
        } else {
            self::abort(404);
        }
        if ($call instanceof \Closure) {
            if (empty($this->config['enable_closure_getter'])) {
                $command = new class ($this) extends Command {};
            } else {
                $command = new class ($this) extends Command {
                    use Getter;
                };
            }
            $ref  = new \ReflectionFunction($call);
            $call = \Closure::bind($call, $command);
        } else {
            if (!is_subclass_of($class = $this->getControllerClass($call), Command::class)) {
                throw new \RuntimeException('call error');
            }
            $ref  = new \ReflectionMethod($class, '__invoke');
            $call = new $class($this);
        }
    }
    
    protected function error($code = null, $message = null)
    {
        
    }
    
    protected function response(int $return = null)
    {
        self::exit(2);
        exit($return);
    }
    
    protected function parseArgv()
    {
        $argv = $_SERVER['argv'];
        $script = array_shift($argv);
        if (($count = count($_SERVER['argv'])) > 0) {
            
        }
    }
    
    protected function autoComplete()
    {
        
    }
}
