<?php
namespace framework\driver\queue\producer;

class Beanstalkd extends Producer
{
    protected function init($connection)
    {
        $connection->useTube($this->job);
        $this->queue = $connection;
    }
    
    public function push($value)
    {
        return $this->queue->put($this->serialize($value));//, $delay)
    }
}
