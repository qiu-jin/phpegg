<?php
namespace framework\driver\queue\producer;

class Redis extends Producer
{
    protected function init($link)
    {
        $this->queue = $link;
    }
    
    public function push($value)
    {
        return $this->queue->lPush($this->job, $this->serialize($value));
    }
}
