<?php
namespace framework\core\app;

use framework\App;
use framework\core\Getter;
use framework\core\Command;
use framework\core\Controller;

class Cli extends App
{   
    protected $config = [
        // 默认命令
        'default_commands' => null,
        // 是否启用readline扩展
        'enable_readline'  => true,
        // 默认调用的方法，为空则使用__invoke
        'default_call_method' => null,
        // 匿名函数是否启用Getter魔术方法
        'enable_closure_getter' => true,
    ];
    protected $is_win;
    protected $has_stty;
    protected $parsed_argv;
    protected $enable_readline;
    
    protected $styles = [
        'bold'        => ['1', '22'],
        'underscore'  => ['4', '24'],
        'blink'       => ['5', '25'],
        'reverse'     => ['7', '27'],
        'conceal'     => ['8', '28'],
        'foreground' => [
            'black'   => ['30', '39'],
            'red'     => ['31', '39'],
            'green'   => ['32', '39'],
            'yellow'  => ['33', '39'],
            'blue'    => ['34', '39'],
            'magenta' => ['35', '39'],
            'cyan'    => ['36', '39'],
            'white'   => ['37', '39'],
        ],
        'background' => [
            'black'   => ['40', '49'],
            'red'     => ['41', '49'],
            'green'   => ['42', '49'],
            'yellow'  => ['43', '49'],
            'blue'    => ['44', '49'],
            'magenta' => ['45', '49'],
            'cyan'    => ['46', '49'],
            'white'   => ['47', '49'],
        ],
    ];
    protected $templates = [
        'error'     => ['foreground' => 'white', 'background' => 'red'],
        'info'      => ['foreground' => 'green'],
        'comment'   => ['foreground' => 'yellow'],
        'question'  => ['foreground' => 'black', 'background' => 'cyan'],
        'highlight' => ['foreground' => 'red'],
        'warning'   => ['foreground' => 'black', 'background' => 'yellow'],
    ];

    public function command(...$params)
    {
        $count = count($params);
        if ($count === 1) {
            if (is_array($params[0])) {
                $this->dispatch = $params[0] + $this->dispatch;
            } else {
                $this->dispatch = $params[0];
                $this->parsed_argv['name'] = null;
            }
            return $this;
        } elseif ($count === 2) {
            $this->dispatch[$params[0]] = $params[1];
            return $this;
        }
        throw new \RuntimeException('Command params error');
    }
    
    public function read($prompt = null, array $auto_complete = null)
    {
        if ($auto_complete === null || !$this->enable_readline) {
            $this->write($prompt);
    		return fgets(STDIN);
        }
        return $this->inputAutoComplete($prompt, $auto_complete);
    }
    
    public function write($text, $style = null)
    {
        if ($style === true) {
            $text = $this->outputTemplate($text);
        } elseif (is_array($style)) {
            $text = $this->outputFormat($text, $style);
        }
        fwrite(STDOUT, $text);
    }
    
    public function isWin()
    {
        return $this->is_win ?? $this->is_win = stripos(PHP_OS, 'win') === 0;
    }
    
    public function hasStty()
    {
        if (isset($this->has_stty)) {
            return $this->has_stty;
        }
        exec('stty 2>&1', $tmp, $code);
        return $this->has_stty = $code === 0;
    }
    
    public function getParsedArgv()
    {
        return $this->parsed_argv;
    }
    
    protected function dispatch()
    {
        if (!self::IS_CLI) {
            throw new \RuntimeException('NOT CLI SAPI');
        }
        $this->enable_readline = !empty($this->config['enable_readline']) && extension_loaded('readline');
        return $this->config['default_commands'] ?? [];
    }
    
    protected function call()
    {
        $this->parseArgv();
        if (($name = $this->parsed_argv['name']) === null) {
            $dispatch = $this->dispatch;
        } elseif (isset($this->dispatch[$name])) {
            $dispatch = $this->dispatch[$name];
        } else {
            self::abort(404);
        }
        if ($dispatch instanceof \Closure) {
            if (empty($this->config['enable_closure_getter'])) {
                $command = new class ($this) extends Command {};
            } else {
                $command = new class ($this) extends Command {
                    use Getter;
                };
            }
            $ref  = new \ReflectionFunction($dispatch);
            $call = \Closure::bind($dispatch, $command, Command::class);
        } else {
            if (!is_subclass_of($dispatch, Command::class)) {
                throw new \RuntimeException('Not is command subclass');
            }
            $method = $this->config['default_call_method'] ?? '__invoke';
            $ref    = new \ReflectionMethod($dispatch, $method);
            $call   = [new $dispatch($this), $method];
        }
        if (empty($this->parsed_argv['params'])) {
            return $call();
        }
        if (($params = Controller::methodBindListParams($ref, $this->parsed_argv['params'])) === false) {
            self::abort(400);
        }
        return $call(...$params);
    }
    
    protected function error($code = null, $message = null)
    {
        var_dump($code, $message);
    }
    
    protected function response($return = null)
    {
        self::exit(2);
        exit((int) $return);
    }
    
    protected function parseArgv()
    {
        $argv = $_SERVER['argv'];
        array_shift($argv);
        if (empty($this->parsed_argv) && !($this->parsed_argv['name'] = array_shift($argv))) {
            self::abort(404);
        }
        if (($count = count($argv)) > 0) {
            $is_option = false;
            for ($i = 0; $i < $count; $i++) {
                if (!$is_option && strpos($argv[$i], '-') === false) {
                    $this->parsed_argv['params'][] = $argv[$i];
                    continue;
                }
    			$is_option = true;
    			if (substr($argv[$i], 0, 1) !== '-') {
    				continue;
    			}
    			$arg = str_replace('-', '', $argv[$i]);
    			$value = null;
    			if (isset($argv[$i + 1]) && substr($argv[$i + 1], 0, 1) != '-') {
    				$value = $argv[$i + 1];
    				$i++;
    			}
    			$this->parsed_argv['options'][$arg] = $value;
    			$is_option = false;
            }
        }
    }
    
    protected function outputFormat($text, $style)
    {
        $str = '';
        if (isset($style['foreground']) && isset($this->styles['foreground'][$style['foreground']])) {
            $str .= "\033[".$this->styles['foreground'][$style['foreground']]."m";
        }
        if (isset($style['background']) && isset($this->styles['background'][$style['background']])) {
            $str .= "\033[".$this->styles['background'][$style['background']]."m";
        }
        if (!empty($style['underline'])) {
            $str .= "\033[4m";
        }
        if ($str) {
            $text = $str.$text."\033[0m";
        }
        if (!empty($style['newline'])) {
            $text .= str_repeat(PHP_EOL, $style['newline']);
        }
        return $text;
    }
    
    protected function outputTemplate($text)
    {
        $offset = 0;
        $output = '';
        $regex  = '[a-z][a-z0-9_=;-]*';
        if (preg_match_all("#<(($regex) | /($regex)?)>#isx", $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $i => $match) {
                $pos  = $match[1];
                $text = $match[0];
                if (0 != $pos && '\\' == $message[$pos - 1]) {
                    continue;
                }
                $output .= $this->applyCurrentStyle(substr($message, $offset, $pos - $offset));
                $offset = $pos + strlen($text);
                if ($open = '/' != $text[1]) {
                    $tag = $matches[1][$i][0];
                } else {
                    $tag = isset($matches[3][$i][0]) ? $matches[3][$i][0] : '';
                }
                if (!$open && !$tag) {
                    // </>
                    $this->styleStack->pop();
                } elseif (false === $style = $this->createStyleFromString(strtolower($tag))) {
                    $output .= $this->applyCurrentStyle($text);
                } elseif ($open) {
                    $this->styleStack->push($style);
                } else {
                    $this->styleStack->pop($style);
                }
            }
            $output .= $this->applyCurrentStyle(substr($message, $offset));
            return str_replace('\\<', '<', $output);
        }
    }
    
    protected function inputAutoComplete($prompt, $auto_complete)
    {
		if (!$this->enable_readline) {
            $this->write($prompt);
    		return fgets(STDIN);
		}
        readline_completion_function(function ($input, $index) use ($auto_complete) {
            if ($input === '') {
                return $auto_complete;
            }
            return array_filter($auto_complete, function ($value) use ($input) {
                return stripos($value, $input) === 0 ? $value : false;
            });
        });
        $input = readline($prompt);
        readline_completion_function(function () {});
        return $input;
    }
}
