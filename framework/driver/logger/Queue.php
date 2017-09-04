<?php
namespace framework\driver\logger;

class Queue extends Logger
{
    protected $producer;
    
    public function __construct($config)
    {
        if (isset($config['queue']) && isset($config['job'])) {
            try {
                $this->producer = make('queue', $config['queue'])->producer($config['job']);
            } catch (\Throwable $e) {
                return $this->send = false;
                //忽略异常
            }
        } else {
            $this->send = false;
        }
    }
    
    public function write($level, $message, $context)
    {
        $this->send && $this->producer->push([$level, $message, $context]);
    }
}
