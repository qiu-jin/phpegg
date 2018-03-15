<?php
namespace framework\driver\queue\producer;

class Beanstalkd extends Producer
{
    protected function init($connection)
    {
        $connection->useTube($this->job);
        return $connection;
    }
    
    public function push($value)
    {
        return $this->producer->put($this->serialize($value));//, $delay)
    }
}
