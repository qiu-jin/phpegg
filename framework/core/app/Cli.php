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
    
    protected $shell;
    protected $is_win;
    protected $parsed_argv;
    protected $enable_stty;
    protected $enable_readline;
    protected $styles = [
        'underline'  => '4',
        'foreground' => [
    		'black'         => '0;30',
    		'dark_gray'     => '1;30',
    		'blue'          => '0;34',
    		'dark_blue'     => '1;34',
    		'light_blue'    => '1;34',
    		'green'         => '0;32',
    		'light_green'   => '1;32',
    		'cyan'          => '0;36',
    		'light_cyan'    => '1;36',
    		'red'           => '0;31',
    		'light_red'     => '1;31',
    		'purple'        => '0;35',
    		'light_purple'  => '1;35',
    		'light_yellow'  => '0;33',
    		'yellow'        => '1;33',
    		'light_gray'    => '0;37',
    		'white'         => '1;37',
        ],
        'background' => [
            'black'         => '40',
            'red'		    => '41',
            'green'		    => '42',
            'yellow'	    => '43',
    		'blue'		    => '44',
    		'magenta'	    => '45',
    		'cyan'		    => '46',
    		'light_gray'    => '47',
        ]
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
        if ($auto_complete === null) {
            $this->write($prompt);
    		return fgets(STDIN);
        }
        return $this->autoComplete($prompt, $auto_complete);
    }
    
    public function write($text, $style = null)
    {
        fwrite(STDOUT, $style ? $this->outputFormat($text, $style) : $text);
    }
    
    public function isWin()
    {
        return $this->is_win;
    }
    
    public function hasStty()
    {
        if (isset($this->stty)) {
            return $this->stty;
        }
        exec('stty 2>&1', $output, $exitcode);
        return $this->stty = $exitcode === 0;
    }
    
    public function getShell()
    {
        if (isset($this->shell)) {
            return $this->shell;
        }
        if (file_exists('/usr/bin/env')) {
            $test = "/usr/bin/env %s -c 'echo OK' 2> /dev/null";
            foreach (['bash', 'zsh', 'ksh', 'csh'] as $sh) {
                if ('OK' === rtrim(shell_exec(sprintf($test, $sh)))) {
                    return $this->shell = $sh;
                }
            }
        }
        return $this->shell = false;
    }
    
    public function readHidden()
    {
        $shell_script = 'x=0
while : ;do
    char=`
        stty cbreak -echo
        dd if=/dev/tty bs=1 count=1 2>/dev/null
        stty -cbreak echo
    `
    if [ "$char" = "" ];then
        break
    fi
    if [[ "$ret" == $(echo -ne \'\b\') ]];then
        if [ $x -eq 0 ];then
            continue
        fi
        password="${password%?}"
        printf "33[1D"
        printf "33[K"
        let x--
        continue
    fi
    password="$password$char"
    echo -n "*"
    let x++
done';

        return rtrim(shell_exec(sprintf("/usr/bin/env %s -c '%s'", 'bash', $shell_script)));
        
          
        
        
        
        
        
        
        
        if ($this->isWin()) {
            
        }
        /*
        if ($this->hasStty()) {
            $sttyMode = shell_exec('stty -g');
            shell_exec('stty -echo');
            $value = fgets(STDIN, 4096);
            shell_exec(sprintf('stty %s', $sttyMode));
            if (false === $value) {
                throw new \RuntimeException('Aborted');
            }
            $value = trim($value);
            $this->write(PHP_EOL);
            return $value;
        }
        if ($shell = $this->getShell()) {
            $readCmd = $shell === 'csh' ? 'set mypassword = $<' : 'read -r mypassword';
            $command = sprintf("/usr/bin/env %s -c 'stty -echo; %s; stty echo; echo \$mypassword'", $shell, $readCmd);
            $value   = rtrim(shell_exec($command));
            $this->write(PHP_EOL);
            return $value;
        }*/
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
        $this->is_win = stripos(PHP_OS, 'win') === 0;
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
    
    protected function autoComplete($prompt, $auto_complete)
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
}
