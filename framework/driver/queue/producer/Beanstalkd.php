<?php
namespace framework\driver\queue\producer;

class Beanstalkd extends Producer
{
    protected $delay;
    
    protected function init($connection, $job, $config)
    {
        if (isset($config['delay'])) {
            $this->delay = $config['delay'];
        }
        $connection->useTube($job);
        return $connection;
    }
    
    public function push($value, $delay = null)
    {
        return $this->producer->put($this->serialize($value), $delay ?? $this->delay ?? 0);
    }
}
