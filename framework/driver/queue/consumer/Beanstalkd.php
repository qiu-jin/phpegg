<?php
namespace framework\driver\queue\consumer;

class Beanstalkd extends Consumer
{
    protected $queue;
    
    public function __construct($queue, $job)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->queue->watch($job);
    }
    
    public function get()
    {
        $data = $this->queue->reserve();
        $this->delete($data);
        return $data;
    }

    public function pop()
    {
        return $this->queue->reserve();
    }
    
    public function bury($id)
    {
        return $this->queue->bury($id, $this->job);
    }
    
    public function kick($time)
    {
        return $this->queue->kick($time, $this->job);
    }
    
    public function ignore()
    {
        $this->queue->ignore($this->job);
    }
    
    public function delete($id)
    {
        return $this->queue->delete($id);
    }
}
