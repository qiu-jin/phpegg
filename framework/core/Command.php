<?php
namespace framework\core;

use framework\App;

abstract class Command
{
    protected $app;
    protected $argv;
    protected $options;
    protected $short_to_long = [
        'h' => 'help',
        'l' => 'list',
        'v' => 'version',
    ];
    
    public function __construct($app = null)
    {
        $this->app = $app ?? App::instance();
        $this->argv = $this->app->getArgv();
        $this->options = $this->argv['long_options'] ?? [];
        if (isset($this->argv['short_options']) && isset($this->short_to_long)) {
            foreach ($this->argv['short_options'] as $k => $v) {
                if (isset($this->short_to_long[$k])) {
                    $this->options[$this->short_to_long[$k]] = $v;
                }
            }
        }
    }
    
    protected function ask($prompt, array $auto_complete = null)
    {
        return $this->app->read($prompt, $auto_complete);
    }
    
    protected function confirm($prompt)
    {
        return in_array(strtok($this->app->read($prompt)), ['y', 'yes'], true);
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
    
    protected function writeln($text, $style = null)
    {
        $this->app->write($text, ['newline' => 1] + $style);
    }
    
    protected function newline($num = 1)
    {
        $this->app->write(null, ['newline' => $num]);
    }
    
    protected function error($text)
    {
        $this->writeln($text, ['color' => 'red']);
    }
    
    protected function info($text)
    {
        $this->writeln($text, ['color' => 'green']);
    }
    
    protected function param(int $index = null)
    {
        return $index === null ? $this->argv['params'] : ($this->argv['params'][$index + 1] ?? null);
    }
    
    protected function option($name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }
    
    protected function longOption($name, $default = null)
    {
        return $this->argv['long_options'][$name] ?? $default;
    }
    
    protected function shortOption($name, $default = null)
    {
        return $this->argv['short_options'][$name] ?? $default;
    }
    
    public function __tostring()
    {
        
    }
}
