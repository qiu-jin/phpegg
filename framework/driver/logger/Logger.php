<?php
namespace framework\driver\logger;

abstract class Logger
{
    protected $send = true;
    protected $formatter;
    
    public function __construct($config)
    {
        $this->init($config);
        if (isset($config['format'])) {
            $this->formatter = new formatter\Formatter($config['format']);
        }
    }
    
    public function write($level, $message, $context = null)
    {
        //write to Null
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
