<?php
namespace Framework\Extend\Logger;

trait Writer
{   
    protected $logs = [];
    
    public function write($level, $message, $context = [])
    {
        //write to NullLogger
    }
    
    public function log($level, $message, $context = [])
    {
        $this->write($level, $message, $context);
    }
    
    public function emergency($message, $context = [])
    {
        $this->write('emergency', $message, $context);
    }
    
    public function alert($message, $context = [])
    {
        $this->write('alert', $message, $context);
    }
    
    public function critical($message, $context = [])
    {
        $this->write('critical', $message, $context);
    }
    
    public function error($message, $context = [])
    {
        $this->write('error', $message, $context);
    }
    
    public function warning($message, $context = [])
    {
        $this->write('warning', $message, $context);
    }
    
    public function notice($message, $context = [])
    {
        $this->write('notice', $message, $context);
    }
    
    public function info($message, $context = [])
    {
        $this->write('info', $message, $context);
    }
    
    public function debug($message, $context = [])
    {
        $this->write('debug', $message, $context);
    }
}
