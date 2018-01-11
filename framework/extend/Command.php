<?php
namespace framework\extend;
    
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
}