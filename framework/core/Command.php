<?php
namespace framework\core;

use framework\App;

abstract class Command
{
    protected $app;
    protected $argv;
    
    public function __construct($app = null)
    {
        $this->app = $app ?? App::instance();
        $this->argv = $this->app->getArgv();
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
        return $index === null ? $this->argv : $this->argv[$index + 1] ?? null;
    }
    
    protected function option($name, $default = null)
    {
        
    }
    
    protected function longOption($name, $default = null)
    {
        
    }
    
    protected function shortOption($name, $default = null)
    {
        
    }
    
    public function __tostring()
    {
        
    }
}
