<?php
namespace framework\driver\queue\consumer;

class Redis extends Consumer
{
    protected function init($connection)
    {
        return $connection;
    }
    
    public function pop()
    {
        return $this->consumer->rPop($this->job);
    }
    
    public function bpop()
    {
        return $this->consumer->brPop($this->job, $this->timeout);
    }
    
    public function consume(callable $call)
    {
        while (true) {
            if ($job = $this->bpop()) {
                $message = $this->unserialize($job[1]);
                if ($call($message) === false) {
                    $this->consumer->lPush($this->job, $job[1]);
                }
            }
        }
    }
}
