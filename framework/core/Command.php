<?php
namespace framework\core;

use framework\App;

abstract class Command
{
    protected $app;
    
    public function __construct($app = null)
    {
        $this->app = $app ?? App::instance();
    }
    
    protected function ask()
    {
        
    }
    
    protected function secret()
    {
        
    }
    
    protected function confirm()
    {
        
    }

    protected function choice()
    {
        
    }
    
    protected function write()
    {
        
    }
    
    protected function writeln()
    {
        
    }
    
    protected function param()
    {
        
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
