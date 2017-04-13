<?php
namespace framework\driver\logger;

class Queue extends Logger
{
    protected $producer;
    
    public function __construct($config)
    {
        if (isset($config['queue']) && isset($config['job'])) {
            try {
                $this->producer = load('queue', $config['queue'])->producer($config['job']);
            } finally {
                $this->send = false;
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
