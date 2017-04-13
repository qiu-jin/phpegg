<?php
namespace framework\driver\queue\consumer;

class Redis
{
    protected $job;
    protected $queue;
    
    public function __construct($queue, $job)
    {
        $this->job = $job;
        $this->queue = $queue;
    }

    public function pop()
    {
        return $this->link->brPop($this->job);
    }
}
