<?php
namespace framework\core;

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
        if (isset($this->title)) {
            $this->title($this->title);
        }
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
    
    protected function ask($prompt, array $auto_complete = null)
    {
        return $this->app->read($prompt, $auto_complete);
    }
    
    protected function confirm($prompt)
    {
        return in_array(strtolower($this->app->read($prompt)), ['y', 'yes'], true);
    }

    protected function choice($prompt, array $options)
    {
        
    }
    
    protected function hidden()
    {
        
    }
    
    protected function write($text, $style = null)
    {
        $this->app->write($text, $style);
    }
    
    protected function line($text, $style = null)
    {
        $this->app->write($text, ['newline' => 1] + ($style ?? []));
    }
    
    protected function error($text)
    {
        $this->writeln($text, ['color' => 'red']);
    }
    
    protected function info($text)
    {
        $this->writeln($text, ['color' => 'green']);
    }
    
    protected function table($data)
    {
        $this->write();
    }
    
    public function __tostring()
    {
        
    }
}
