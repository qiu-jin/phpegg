<?php
namespace framework\core\app;

use framework\App;

class Cli extends App
{
    private $pid;
    private $option;
    
    public function dispatch()
    {
        if (PHP_SAPI === 'cli' || defined('STDIN')) {
            define('IS_CLI', true);
            $this->pid = getmypid();
            $this->option = getopt('m:c:a:');
        }
        return false;
    }
    
    public function run()
    {
        
    }
    
    public function error($code = null, $message = null)
    {
        file_put_contents('php://stderr', json_encode([$code, $message]));
    }
    
    protected function response($return)
    {
        file_put_contents('php://stdou', json_encode($return));
    }
}
