<?php
namespace framework\driver\logger;

abstract class Logger
{
    use \framework\extend\logger\Writer;
    
    protected $send = true; 
    
    abstract public function write($level, $message, $context);
}
