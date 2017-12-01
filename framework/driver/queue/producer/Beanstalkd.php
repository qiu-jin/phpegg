<?php
namespace framework\driver\queue\producer;

class Beanstalkd extends Producer
{
    protected function init($link)
    {
        $link->useTube($this->job);
        $this->queue = $link;
    }
    
    public function push($value)
    {
        return $this->queue->put($this->serialize($value));//, $delay)
    }
}
