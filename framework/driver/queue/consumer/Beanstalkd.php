<?php
namespace framework\driver\queue\consumer;

class Beanstalkd extends Consumer
{   
    protected function init($connection, $job)
    {
        $connection->watch($job);
        return $connection;
    }
    
    public function bpop()
    {
        if ($job = $this->queue->reserve()) {
            $message = $this->unserialize($job->getData());
            $this->queue->delete($job);
            return $message;
        }
    }
    
    public function consume(callable $call)
    {
        while (true) {
            if ($job = $this->queue->reserve()) {
                $message = $this->unserialize($job->getData());
                if ($call($message) === false) {
                    $this->queue->release($job);
                } else {
                    $this->queue->delete($job);
                }
            }
        }
    }
}
