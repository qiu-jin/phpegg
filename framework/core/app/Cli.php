<?php
namespace framework\core\app;

use framework\App;
use framework\core\Loader;
use framework\core\Getter;
use framework\core\Command;

class Cli extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns' => 'command',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 默认调用的方法，空则调用__invoke
        'controller_default_call_method' => null,
        // 匿名函数是否启用Getter魔术方法
        'closure_enable_getter' => true,
        // Getter providers
        'closure_getter_providers'  => null,
    ];
    // 核心错误
    protected $core_errors = [
        404 => 'Bad Request',
        404 => 'Method not found',
        500 => 'Internal Server Error',
    ];
    
    /*
     * 设置命令行调用
     */
    public function command($name, $call = null)
    {
        if ($call !== null) {
            $this->dispatch['commands'][$name] = $call;
        } else {
            if (is_array($name)) {
                $this->dispatch['commands'] = $name + $this->dispatch['commands'];
            } else {
                $this->dispatch['commands'] = $name;
                $this->dispatch['arguments']['name'] = null;
            }
        }
        return $this;
    }
    
    /*
     * 调度
     */
    protected function dispatch()
    {
        if (App::IS_CLI) {
            return true;
        }
		throw new \RuntimeException('NOT CLI SAPI');
    }
    
    /*
     * 调用
     */
    protected function call()
    {
        $this->parseArguments();
        if (($name = $this->dispatch['arguments']['name']) === null) {
            $dispatch = $this->dispatch['commands'];
        } elseif (isset($this->dispatch['commands'][$name])) {
            $dispatch = $this->dispatch['commands'][$name];
        } else {
            self::abort(404);
        }
        $templates = $this->config['templates'] ?? null;
        $arguments = $this->dispatch['arguments'] ?? null;
        if ($dispatch instanceof \Closure) {
            if (!$this->config['closure_enable_getter']) {
                $command = new class ($arguments, $templates) extends Command {};
            } else {
                $providers = $this->config['closure_getter_providers'];
                $command = new class ($providers, $arguments, $templates) extends Command {
                    use Getter;
                    public function __construct($providers, $arguments, $templates) {
                        if ($providers) {
                            $this->{\app\env\GETTER_PROVIDERS_NAME} = $providers;
                        }
                        parent::__construct($arguments, $templates);
                    }
                };
            }
            $this->dispatch['instance'] = $command;
            $call = \Closure::bind($dispatch, $command, Command::class);
        } elseif (is_string($dispatch)) {
            Loader::add('alias', ['Command' => Command::class]);
            $class = $this->getControllerClass($dispatch);
            if (!is_subclass_of($class, Command::class)) {
                throw new \RuntimeException('Not is command subclass');
            }
            $call = $this->dispatch['instance'] = new $class($arguments, $templates);
            if ($this->config['controller_default_call_method']) {
                $call = [$call, $this->config['controller_default_call_method']];
            }
        } else {
            throw new \RuntimeException('Invalid command call type');
        }
        return $call(...($arguments['params'] ?? []));
    }
    
    /*
     * 错误
     */
    protected function error($code = null, $message = null)
    {
        $command = $this->dispatch['instance'] ?? new Command;
        $command->error("[$code]");
        $command->highlight(is_array($message) ? var_export($message, true) : $message);
    }
    
    /*
     * 响应
     */
    protected function respond($return = null)
    {
        self::exit(2);
        exit((int) $return);
    }
    
    /*
     * 解析命令行参数
     */
    protected function parseArguments()
    {
        $argv = $_SERVER['argv'];
        array_shift($argv);
        if (!$this->dispatch['arguments'] && !($this->dispatch['arguments']['name'] = array_shift($argv))) {
            self::abort(404);
        }
        if (($count = count($argv)) === 0) {
            return;
        }
        $last_option = null;
        for ($i = 0; $i < $count; $i++) {
            if (strpos($argv[$i], '-') !== 0) {
                if ($last_option) {
                    $this->dispatch['arguments']["$last_option[0]_options"][$last_option[1]] = $argv[$i];
                    $last_option = null;
                } else {
                    $this->dispatch['arguments']['params'][] = $argv[$i];
                }
            } else {
                $last_option = null;
                if (strpos($argv[$i], '--') === 0) {
                    if ($option_name = substr($argv[$i], 2)) {
                        if (strpos($option_name, '=') > 0) {
                            list($k, $v) = explode('=', $option_name, 2);
                            $this->dispatch['arguments']['long_options'][$k] = $v;
                        } else {
                            $this->dispatch['arguments']['long_options'][$option_name] = true;
                            $last_option = ['long', $option_name];
                        }
                    }
                } else {
                    if ($option_name = substr($argv[$i], 1)) {
                        if (isset($option_name[1])) {
                            $this->dispatch['arguments']['short_options'][$option_name[0]] = substr($option_name, 1);
                        } elseif (isset($option_name[0])) {
                            $this->dispatch['arguments']['short_options'][$option_name] = true;
                            $last_option = ['short', $option_name];
                        }
                    }
                }
            }
        }
    }
}

