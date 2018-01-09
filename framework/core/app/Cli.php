<?php
namespace framework\core\app {

use framework\App;
use framework\util\Arr;
use framework\core\Loader;
use framework\core\Getter;
use framework\core\Command;
use framework\core\Controller;

class Cli extends App
{
    protected $config = [
        // 默认命令
        //'default_commands' => null,
        // 默认调用的方法，为空则使用__invoke
        'default_call_method' => null,
        // 匿名函数是否启用Getter魔术方法
        'enable_closure_getter' => true,
    ];
    // 是否为windows系统
    protected $is_win;
    // 是否有stty命令工具
    protected $has_stty;
    // 是否启用readline扩展
    protected $has_readline;
    // 已解析的输入参数
    protected $parsed_argv;
    // 终端输出样式
    protected $styles = [
        'bold'        => ['1', '22'],
        'underscore'  => ['4', '24'],
        'blink'       => ['5', '25'],
        'reverse'     => ['7', '27'],
        'conceal'     => ['8', '28'],
        'foreground'  => [
            'black'   => ['30', '39'],
            'red'     => ['31', '39'],
            'green'   => ['32', '39'],
            'yellow'  => ['33', '39'],
            'blue'    => ['34', '39'],
            'magenta' => ['35', '39'],
            'cyan'    => ['36', '39'],
            'white'   => ['37', '39'],
        ],
        'background'  => [
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
    // 输出样式模版
    protected $templates = [
        'error'     => ['foreground' => 'white', 'background' => 'red'],
        'info'      => ['foreground' => 'green'],
        'comment'   => ['foreground' => 'yellow'],
        'question'  => ['foreground' => 'black', 'background' => 'cyan'],
        'highlight' => ['foreground' => 'red'],
        'warning'   => ['foreground' => 'black', 'background' => 'yellow'],
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
    
    public function read($prompt = null)
    {
        if ($prompt !== null) {
            $this->write($this->formatTemplate($prompt));
        }
		return fgets(STDIN);
    }
    
    public function write($text, $style = null)
    {
        if ($style === true) {
            $text = $this->formatTemplate($text);
        } elseif (is_array($style)) {
            $text = $this->formatStyle($text, $style);
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
    
    public function hasReadline()
    {
        return $this->has_readline ?? $this->has_readline = extension_loaded('readline');
    }
    
    public function getParsedArgv()
    {
        return $this->parsed_argv;
    }
    
    public function formatStyle($text, $style)
    {
        if ($style) {
            foreach ($style as $k => $v) {
                if ($k === 'foreground' || $k === 'background') {
                    if (isset($this->styles[$k][$v])) {
                        list($start[], $end[]) = $this->styles[$k][$v];
                    }
                } elseif (isset($this->styles[$k])) {
                    if ($this->styles[$k]) {
                        list($start[], $end[]) = $this->styles[$k];
                    }
                }
            }
            if (isset($start)) {
                return "\033[".implode(';', $start)."m$text\033[".implode(';', $end)."m";
            }
        }
        return $text;
    }
    
    public function formatTemplate($text)
    {
        $regex = '[a-z][a-z0-9_=;-]*';
        if (!preg_match_all("#<(($regex) | /($regex)?)>#isx", $text, $matches, PREG_OFFSET_CAPTURE)) {
            return $text;
        }
        $offset = 0;
        $stack  = [];
        $output = '';
        foreach ($matches[0] as $i => $match) {
            list($tag, $pos) = $match;
            $part = substr($text, $offset, $pos - $offset);
            if ($tag[1] !== '/') {
                $count   = count($stack);
                $stack[] = ['tag' => substr($tag, 1, -1), 'text' => ''];
            } else {
                if (($last = array_pop($stack)) && $last['tag'] === substr($tag, 2, -1)) {
                    $count = count($stack);
                    $part  = $this->formatStyle($last['text'].$part, $this->templates[$last['tag']]);
                } else {
                    throw new \RuntimeException('Template output error');
                }
            }
            if ($count === 0) {
                $output .= $part;
            } else {
                $stack[$count - 1]['text'] .= $part;
            }
            $offset = $pos + strlen($tag);
        }
        if ($stack) {
            throw new \RuntimeException('Template output error');
        }
        return str_replace('\\<', '<', $output.substr($text, $offset));
    }
    
    protected function dispatch()
    {
        if (!App::IS_CLI) {
            throw new \RuntimeException('NOT CLI SAPI');
        }
        if (is_array($templates = Arr::pull($this->config, 'templates'))) {
            $this->templates += $templates;
        }
        return Arr::pull($this->config, 'default_commands', []);
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
            Loader::add('alias', ['Command' => Command::class]);
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
        $status = $this->formatStyle(($this->core_errors[$code] ?? $code).':', ['bold' => true]);
        fwrite(STDERR, $this->formatTemplate("<error>$status</error>").var_export($message, true).PHP_EOL);
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
        if (($count = count($argv)) === 0) {
            return;
        }
        $last_option = null;
        for ($i = 0; $i < $count; $i++) {
            if (strpos($argv[$i], '-') !== 0) {
                if ($last_option) {
                    $this->parsed_argv["$last_option[0]_options"][$last_option[1]] = $argv[$i];
                    $last_option = null;
                } else {
                    $this->parsed_argv['params'][] = $argv[$i];
                }
            } else {
                $last_option = null;
                if (strpos($argv[$i], '--') === 0) {
                    if ($option_name = substr($argv[$i], 2)) {
                        if (strpos($option_name, '=') > 0) {
                            list($k, $v) = explode('=', $option_name, 2);
                            $this->parsed_argv['long_options'][$k] = $v;
                        } else {
                            $this->parsed_argv['long_options'][$option_name] = true;
                            $last_option = ['long', $option_name];
                        }
                    }
                } else {
                    if ($option_name = substr($argv[$i], 1)) {
                        if (isset($option_name[1])) {
                            $this->parsed_argv['short_options'][$option_name[0]] = substr($option_name, 1);
                        } elseif (isset($option_name[0])) {
                            $this->parsed_argv['short_options'][$option_name] = true;
                            $last_option = ['short', $option_name];
                        }
                    }
                }
            }
        }
    }
}}


namespace framework\core {
    
use framework\App;
use framework\core\app\Cli;

abstract class Command
{
    private $app;
    private $pid;
    private $argv;
    
    protected $title;
    protected $options;
    protected $short_option_alias;
    
    public function __construct($app = null)
    {
        $this->app = $app ?? App::instance();
        if (!$this->app instanceof Cli) {
            throw new \RuntimeException('Not is Cli mode');
        }
        if (isset($this->title)) {
            $this->title($this->title);
        }
        $this->argv = $this->app->getParsedArgv();
        $this->options = $this->argv['long_options'] ?? [];
        if (isset($this->argv['short_options'])) {
            if ($this->short_option_alias) {
                foreach ($this->argv['short_options'] as $k => $v) {
                    $option = $this->short_option_alias[$k] ?? null;
                    if ($option && !isset($this->options[$option])) {
                        $this->options[$option] = $v;
                    }
                }
            }
            $this->options += $this->argv['short_options'];
        }
    }
    
    protected function app()
    {
        return $this->app;
    }
    
    protected function pid()
    {
        return $this->pid ?? $this->pid = getmypid();
    }
    
    protected function title($title)
    {
        cli_set_process_title($title);
    }
    
    protected function params()
    {
        return $this->argv['params'] ?? null;
    }
    
    protected function param(int $index, $default = null)
    {
        return $this->argv['params'][$index - 1] ?? $default;
    }
    
    protected function options()
    {
        return $this->options ?? null;
    }
    
    protected function option($name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }
    
    protected function longOptions()
    {
        return $this->argv['long_options'] ?? null;
    }
    
    protected function longOption($name, $default = null)
    {
        return $this->argv['long_options'][$name] ?? $default;
    }
    
    protected function shortOptions()
    {
        return $this->argv['short_options'] ?? null;
    }
    
    protected function shortOption($name, $default = null)
    {
        return $this->argv['short_options'][$name] ?? $default;
    }
    
    protected function write($text, $style = null)
    {
        $this->app->write($text, $style);
    }
    
    protected function line($text, $style = null)
    {
        $this->app->write($text, $style);
        $this->newline();
    }
    
    protected function error($text)
    {
        $this->line("<error>$text</error>", true);
    }
    
    protected function info($text)
    {
        $this->line("<info>$text</info>", true);
    }
    
    protected function comment($text)
    {
        $this->line("<comment>$text</comment>", true);
    }
    
    protected function question($text)
    {
        $this->line("<question>$text</question>", true);
    }
    
    protected function highlight($text)
    {
        $this->line("<highlight>$text</highlight>", true);
    }
    
    protected function warning($text)
    {
        $this->line("<warning>$text</warning>", true);
    }
    
    protected function table(array $data, array $head = null)
    {
        $data = array_values($data);
		if ($head) {
			array_unshift($data, $head);
		} elseif (!isset($data[0][0])) {
            array_unshift($data, $head = array_keys($data[0]));
		}
		foreach ($data as $i => $row) {
            $row = array_values($row);
            $data[$i] = $row;
			foreach ($row as $k => $v) {
                $max_width[$k] = max($max_width[$k] ?? 0, mb_strwidth($v));
			}
		}
        $border = '+'.implode('+', array_map(function ($w) {
            return str_repeat('-', $w + 2);
        }, $max_width)).'+';
        foreach ($data as $row) {
            $table[] = '| '.implode(' | ', array_map(function ($w, $v){
                return $v.str_repeat(' ', $w - mb_strwidth($v));
            }, $max_width, $row)).' |';
		}
        $table[] = $border;
        if ($head) {
            array_unshift($table, array_shift($table), $border);
        }
        array_unshift($table, $border);
        $this->line(implode(PHP_EOL, $table));
    }
    
    protected function newline($num = 1)
    {
        $this->app->write(str_repeat(PHP_EOL, $num));
    }
    
    protected function ask($prompt, array $auto_complete = null)
    {
        return $this->app->read($prompt, $auto_complete);
    }
    
    protected function confirm($prompt)
    {
        return in_array(strtolower($this->app->read($prompt)), ['y', 'yes'], true);
    }

    protected function choice($prompt, array $options, $is_multi_select = false)
    {
        
    }
    
    protected function progress($total = 100, $plus = '+', $reduce = '-', $format = '[%s%s] %3d%% Complete')
    {
        return new class ($this->app, compact('total', 'plus', 'reduce', 'format')) {
            private $app;
            private $step;
            private $options;
            public function __construct($app, $options) {
                $this->app = $app;
                $this->options = $options;
            }
            public function add(int $num = 1) {
                $this->step($this->step ? $this->step + $num : $num);
            }
            public function step(int $step) {
                if (isset($this->step)) {
                    $this->app->write("\033[1A");
                }
                $this->step = $step > $this->options['total'] ? $this->options['total'] : $step;
    			$percent = intval(($this->step / $this->options['total']) * 100);
                $this->app->write(sprintf(
                    $this->options['format'],
                    str_repeat($this->options['plus'], $this->step),
                    str_repeat($this->options['reduce'], $this->options['total'] - $this->step),
                    $percent
                ).PHP_EOL);
                //$this->app->write("\007");
            }
        };
    }
    
    protected function hidden($prompt)
    {
        if ($this->app->hasStty()) {
            $this->app->write($prompt);
            $sttyMode = shell_exec('stty -g');
            shell_exec('stty -echo');
            $value = $this->app->read();
            shell_exec(sprintf('stty %s', $sttyMode));
            if (false !== $value) {
                $this->newline();
                return $value;
            }
            throw new \RuntimeException('Aborted');
        }
        throw new \RuntimeException('Unable to hide the response.');
    }
    
    public function anticipate($prompt, array $values)
    {
        if ($this->hasReadline()) {
            readline_completion_function(function ($input, $index) use ($values) {
                if ($input === '') {
                    return $values;
                }
                return array_filter($values, function ($value) use ($input) {
                    return stripos($value, $input) === 0 ? $value : false;
                });
            });
            $input = readline($prompt);
            readline_completion_function(function () {});
            return $input;
        }
        throw new \RuntimeException('Anticipate method must enable readline.');
    }
    
    public function __tostring()
    {
        
    }
}}
