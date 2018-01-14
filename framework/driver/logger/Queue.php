<?php
namespace framework\driver\logger;

use framework\core\Container;

class Queue extends Logger
{
    protected $producer;
    
    protected function init($config)
    {
        $this->producer = Container::driver('queue', $config['queue'])->producer($config['job'] ?? null);
    }
    
    public function write($level, $message, $context = null)
    {
        $this->producer->push(
            $this->formatter ? $this->formatter->make($level, $message, $context) : [$level, $message, $context]
        );
    }
}
