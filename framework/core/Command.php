<?php
namespace framework\core;
    
use framework\App;

class Command
{
    // 应用实例
    private $app;
    // 进程ID
    private $pid;
    // 参数
    private $arguments;
    // 是否为windows系统
    private $is_win;
    // 是否有stty命令工具
    private $has_stty;
    // 是否启用readline扩展
    private $has_readline;
    // 选项值
    private $options;
    // 终端输出样式
    private $styles = [
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
    private $templates = [
        'error'     => ['foreground' => 'white', 'background' => 'red'],
        'info'      => ['foreground' => 'green'],
        'comment'   => ['foreground' => 'yellow'],
        'question'  => ['foreground' => 'black', 'background' => 'cyan'],
        'highlight' => ['foreground' => 'red'],
        'warning'   => ['foreground' => 'black', 'background' => 'yellow'],
    ];
    // 进程标题
    protected $title;
    // 短选项别名
    protected $short_option_alias;
    
    /*
     * 构造函数
     */
    public function __construct(array $arguments = null, array $templates = null)
    {
        if (isset($this->title)) {
            $this->setTitle($this->title);
        }
        if ($arguments) {
            $this->arguments = $arguments;
            $this->options = $this->arguments['long_options'] ?? [];
            if (isset($this->arguments['short_options'])) {
                if ($this->short_option_alias) {
                    foreach ($this->arguments['short_options'] as $k => $v) {
                        $option = $this->short_option_alias[$k] ?? null;
                        if ($option && !isset($this->options[$option])) {
                            $this->options[$option] = $v;
                        }
                    }
                }
                $this->options += $this->arguments['short_options'];
            }
        }
        if ($templates) {
            $this->templates = $templates + $this->templates;
        }
    }
    
    /*
     * 获取进程id
     */
    public function pid()
    {
        return $this->pid ?? $this->pid = getmypid();
    }
    
    /*
     * 获取参数
     */
    public function param(int $index = null, $default = null)
    {
        return $index === null ? ($this->arguments['params'] ?? null) 
							   : ($this->arguments['params'][$index - 1] ?? $default);
    }
    
    /*
     * 获取设置值
     */
    public function option($name = null, $default = null)
    {
        return $name === null ? ($this->options ?? null) : ($this->options[$name] ?? $default);
    }
    
    /*
     * 获取长设置值
     */
    public function longOption($name = null, $default = null)
    {
        return $name === null ? ($this->arguments['long_options'] ?? null)
			                  : ($this->arguments['long_options'][$name] ?? $default);
    }
    
    /*
     * 获取短设置值
     */
    public function shortOption($name = null, $default = null)
    {
        return $name === null ? ($this->arguments['short_options'] ?? null)
			                  : ($this->arguments['short_options'][$name] ?? $default);
    }
    
    /*
     * 读输入
     */
    public function read($prompt = null)
    {
        if ($prompt !== null) {
            $this->write($prompt);
        }
        return fgets(STDIN);
    }
    
    /*
     * 写输出
     */
    public function write($text, $style = null)
    {
        if ($style === true) {
            $text = $this->formatTemplate($text);
        } elseif (is_array($style)) {
            $text = $this->formatStyle($text, $style);
        }
        fwrite(STDOUT, $text);
    }
    
    /*
     * 输出单行
     */
    public function line($text, $style = null)
    {
        $this->write($text, $style);
        $this->newline();
    }
    
    /*
     * 错误
     */
    public function error($text)
    {
        $this->line("<error>$text</error>", true);
    }
	
    /*
     * 警告
     */
    public function warning($text)
    {
        $this->line("<warning>$text</warning>", true);
    }
    
    /*
     * 信息
     */
    public function info($text)
    {
        $this->line("<info>$text</info>", true);
    }
    
    /*
     * 注释
     */
    public function comment($text)
    {
        $this->line("<comment>$text</comment>", true);
    }
    
    /*
     * 高亮
     */
    public function highlight($text)
    {
        $this->line("<highlight>$text</highlight>", true);
    }
	
    /*
     * 
     */
    public function question($text)
    {
        $this->line("<question>$text</question>", true);
    }
    
    /*
     * 格式化json
     */
    public function json($data)
    {
        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /*
     * 表格
     */
    public function table(array $data, array $head = null)
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
    
    /*
     * 换行
     */
    public function newline($num = 1)
    {
        $this->write(str_repeat(PHP_EOL, $num));
    }
    
    /*
     * 问答
     */
    public function ask($prompt, array $auto_complete = null)
    {
        return $this->read($prompt, $auto_complete);
    }
    
    /*
     * 确认
     */
    public function confirm($prompt)
    {
        return in_array(strtolower($this->read($prompt)), ['y', 'yes'], true);
    }

    /*
     * 选择
     */
    public function choice($prompt, array $options, $is_multi_select = false)
    {
        
    }
    
    /*
     * 进度条
     */
    public function progress($total = 100, $plus = '+', $reduce = '-', $format = '[%s%s] %3d%% Complete')
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
                    $this->write("\033[1A");
                }
                $this->step = $step > $this->options['total'] ? $this->options['total'] : $step;
    			$percent = intval(($this->step / $this->options['total']) * 100);
                $this->write(sprintf(
                    $this->options['format'],
                    str_repeat($this->options['plus'], $this->step),
                    str_repeat($this->options['reduce'], $this->options['total'] - $this->step),
                    $percent
                ).PHP_EOL);
                //$this->app->write("\007");
            }
        };
    }
    
    /*
     * 隐藏输入字符（替换为星号*）
     */
    public function hidden($prompt)
    {
        if ($this->hasStty()) {
            $this->write($prompt);
            $sttyMode = shell_exec('stty -g');
            shell_exec('stty -echo');
            $value = $this->read();
            shell_exec(sprintf('stty %s', $sttyMode));
            if (false !== $value) {
                $this->newline();
                return $value;
            }
            throw new \RuntimeException('Aborted');
        }
        throw new \RuntimeException('Unable to hide the response.');
    }
    
    /*
     * 输入补全
     */
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
    
    /*
     * 格式化样式
     */
    public function formatStyle($text, array $style)
    {
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
        return $text;
    }
    
    /*
     * 格式化模版
     */
    protected function formatTemplate($text)
    {
        $regex = implode('|', array_keys($this->templates));
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
                    if (isset($this->templates[$last['tag']])) {
                        $part = $this->formatStyle($last['text'].$part, $this->templates[$last['tag']]);
                    } else {
                        $part = $last['text'].$part;
                    }
                } else {
                    throw new \Exception('Template output error 模版标签未闭合');
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
            throw new \Exception('Template output error 模版标签未闭合');
        }
        return str_replace('\\<', '<', $output.substr($text, $offset));
    }
    
    /*
     * 是否为win系统
     */
    public function isWin()
    {
        return $this->is_win ?? $this->is_win = stripos(PHP_OS, 'win') === 0;
    }
    
    /*
     * 是否安装stty命令工具
     */
    public function hasStty()
    {
        if (isset($this->has_stty)) {
            return $this->has_stty;
        }
        exec('stty 2>&1', $tmp, $code);
        return $this->has_stty = $code === 0;
    }
    
    /*
     * 是否安装readline扩展
     */
    public function hasReadline()
    {
        return $this->has_readline ?? $this->has_readline = extension_loaded('readline');
    }
    
    /*
     * 设置进程标题
     */
    public function setTitle($title)
    {
        cli_set_process_title($title);
    }
}
