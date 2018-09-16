<?php
namespace framework\core\app;

use framework\App;
use framework\util\Arr;
use framework\core\Loader;
use framework\core\Getter;
use framework\core\Command;
use framework\extend\MethodParameter;

class Cli extends App
{
    const INPUT = STDIN;
    const OUTPUT = STDOUT;
    
    protected $config = [
        // 默认调用的方法，为空则使用__invoke
        'default_call_method'   => '__invoke',
        // 匿名函数是否启用Getter魔术方法
        'enable_closure_getter' => true,
        // Getter providers
        'closure_getter_providers'  => null,
    ];
    // 核心错误
    protected $core_errors = [
        404 => 'Bad Request',
        404 => 'Method not found',
        500 => 'Internal Server Error',
    ];

    public function command(...$params)
    {
        $count = count($params);
        if ($count === 1) {
            if (is_array($params[0])) {
                $this->dispatch['commands'] = $params[0] + $this->dispatch['commands'];
            } else {
                $this->dispatch['commands'] = $params[0];
                $this->dispatch['arguments']['name'] = null;
            }
            return $this;
        } elseif ($count === 2) {
            $this->dispatch['commands'][$params[0]] = $params[1];
            return $this;
        }
        throw new \RuntimeException('Command params error');
    }
    
    protected function dispatch()
    {
        if (!App::IS_CLI) {
            throw new \RuntimeException('NOT CLI SAPI');
        }
        return ['commands' => Arr::poll($this->config, 'commands')];
    }
    
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
        $arguments = $this->dispatch['arguments'] ?? null;
        $templates = Arr::poll($this->config, 'templates');
        if ($dispatch instanceof \Closure) {
            if (empty($this->config['enable_closure_getter'])) {
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
            $ref  = new \ReflectionFunction($dispatch);
            $call = \Closure::bind($dispatch, $command, Command::class);
        } else {
            Loader::add('alias', ['Command' => Command::class]);
            if (!is_subclass_of($dispatch, Command::class)) {
                throw new \RuntimeException('Not is command subclass');
            }
            $method = $this->config['default_call_method'];
            $this->dispatch['instance'] = $instance = new $dispatch($arguments, $templates);
            $ref    = new \ReflectionMethod($instance, $method);
            $call   = [$instance, $method];
        }
        if (empty($arguments['params'])) {
            return $call();
        }
        if (($params = MethodParameter::bindListParams($ref, $arguments['params'])) === false) {
            self::abort(400);
        }
        return $call(...$params);
    }
    
    protected function error($code = null, $message = null)
    {
        $text = "[$code] ".is_array($message) ? var_export($message, true) : $message;
        ($this->dispatch['instance'] ?? new Command())->error($text);
    }
    
    protected function response($return = null)
    {
        self::exit(2);
        exit((int) $return);
    }
    
    protected function parseArguments()
    {
        $argv = $_SERVER['argv'];
        array_shift($argv);
        if (empty($this->dispatch['arguments']) && !($this->dispatch['arguments']['name'] = array_shift($argv))) {
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

