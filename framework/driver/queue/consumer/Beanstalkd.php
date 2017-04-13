<?php
namespace framework\driver\queue\consumer;

class Beanstalkd
{
    protected $queue;
    
    public function __construct($queue, $job)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->queue->watch($job);
    }

    public function pop($del = true)
    {
        $data = $this->link->reserve();
        if ($del) {
            $this->delete($id);
        }
    }
    
    public function bury($id)
    {
        return $this->link->bury($id, $this->job);
    }
    
    public function kick($time)
    {
        return $this->link->kick($time, $this->job);
    }
    
    public function ignore()
    {
        $this->link->ignore($this->job);
    }
    
    public function delete($id)
    {
        return $this->link->delete($id, $this->job);
    }
}
