<?php
namespace framework\driver\queue\consumer;

class Beanstalkd extends Consumer
{   
    protected function init($connection, $job)
    {
        $connection->watch($job);
        return $connection;
    }
    
    public function pull($block = true)
    {
        if ($block) {
            if ($job = $this->consumer->reserve()) {
                $message = $this->unserialize($job->getData());
                $this->consumer->delete($job);
                return $message;
            }
        }
        throw new \Exception('Beanstalkd not support no-block pull');
    }
    
    public function consume(callable $call)
    {
        while (true) {
            if ($job = $this->consumer->reserve()) {
                $message = $this->unserialize($job->getData());
                if ($call($message) === false) {
                    $this->consumer->release($job);
                } else {
                    $this->consumer->delete($job);
                }
            }
        }
    }
}
