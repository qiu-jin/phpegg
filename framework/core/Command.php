<?php
namespace framework\core;

use framework\App;

abstract class Command
{
    protected $app;
    protected $argv;
    protected $options;
    
    public function __construct($app = null)
    {
        $this->app = $app ?? App::instance();
        $this->argv = $this->app->getArgv();
        $this->options = $this->argv['long_options'] ?? [];
    }
    
    protected function ask($prompt)
    {
        return $this->app->read($prompt);
    }
    
    protected function confirm($prompt)
    {
        return $this->app->read($prompt) == 'y';
    }

    protected function choice()
    {
        
    }
    
    protected function hidden()
    {
        
    }
    
    protected function write($text)
    {
        $this->app->write($text);
    }
    
    protected function writeln($text)
    {
        $this->app->write($text.PHP_EOL);
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
