<?php
namespace framework\driver\logger;

use framework\core\Container;

class Queue extends Logger
{
    protected $producer;
    
    public function __construct($config)
    {
        if (isset($config['job'])) {
            $config['queue']['job'] = $config['job'];
        }
        $this->producer = Container::driver($config['queue'])->producer();
    }
    
    public function write($level, $message, $context)
    {
        $this->producer->push($this->formatter ? $this->formatter->make($level, $message, $context)
                                               : [$level, $message, $context]
        );
    }
}
