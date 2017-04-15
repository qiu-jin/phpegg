<?php
namespace framework\driver\queue\consumer;

class Redis extends Consumer
{
    protected $job;
    protected $queue;
    
    public function __construct($queue, $job)
    {
        $this->job = $job;
        $this->queue = $queue;
    }
    
    public function get()
    {
        return $this->queue->rpoplpush($this->job, $this->timeout);
    }

    public function pop()
    {
        return $this->queue->brPop($this->job, $this->timeout);
    }
    
    
    public function delete()
    {
        
    }
}
