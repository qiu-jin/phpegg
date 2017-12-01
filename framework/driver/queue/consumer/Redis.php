<?php
namespace framework\driver\queue\consumer;

class Redis extends Consumer
{
    protected function init($link)
    {
        $this->queue = $link;
    }
    
    public function pop()
    {
        return $this->queue->rPop($this->job);
    }
    
    public function bpop()
    {
        return $this->queue->brPop($this->job, $this->timeout);
    }
    
    public function consume(callable $call)
    {
        while (true) {
            if ($job = $this->bpop()) {
                $message = $this->unserialize($job[1]);
                if (!$call($message)) {
                    $this->queue->lPush($this->job, $job[1]);
                }
            }
        }
    }
}
