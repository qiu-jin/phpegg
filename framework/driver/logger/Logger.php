<?php
namespace framework\driver\logger;

abstract class Logger
{
    protected $logs;
    
    public function write($level, $message, $context = null)
    {
        $this->logs[] = [$level, $message, $context];
    }
    
    public function log($level, $message, $context = null)
    {
        $this->write($level, $message, $context);
    }
    
    public function emergency($message, $context = null)
    {
        $this->write('emergency', $message, $context);
    }
    
    public function alert($message, $context = null)
    {
        $this->write('alert', $message, $context);
    }
    
    public function critical($message, $context = null)
    {
        $this->write('critical', $message, $context);
    }
    
    public function error($message, $context = null)
    {
        $this->write('error', $message, $context);
    }
    
    public function warning($message, $context = null)
    {
        $this->write('warning', $message, $context);
    }
    
    public function notice($message, $context = null)
    {
        $this->write('notice', $message, $context);
    }
    
    public function info($message, $context = null)
    {
        $this->write('info', $message, $context);
    }
    
    public function debug($message, $context = null)
    {
        $this->write('debug', $message, $context);
    }
}
