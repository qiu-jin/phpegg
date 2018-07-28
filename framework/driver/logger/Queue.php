<?php
namespace framework\driver\logger;

use framework\core\Event;
use framework\core\Container;

class Queue extends Logger
{
    protected $producer;
    protected $real_write;
    
    protected function init($config)
    {
        $this->producer = Container::driver('queue', $config['queue'])->producer($config['job'] ?? null);
        if (!$this->real_write = $config['real_write'] ?? false) {
            Event::on('close', [$this, 'flush']);
        }
    }
    
    public function write($level, $message, $context = null)
    {
        if ($this->real_write) {
            $this->realWrite($level, $message, $context);
        } else {
            $this->logs[] = [$level, $message, $context];
        }
    }
    
    public function flush()
    {
        if ($this->logs) {
            foreach ($this->logs as $log) {
                $this->realWrite(...$log);
            }
            $this->logs = null;
        }
    }
    
    protected function realWrite($level, $message, $context)
    {
        $this->producer->push(
            $this->formatter ? $this->formatter->make($level, $message, $context) : [$level, $message, $context]
        );
    }
}
