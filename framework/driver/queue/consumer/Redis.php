<?php
namespace framework\driver\queue\consumer;

class Redis extends Consumer
{
    protected $job;
    
    protected function init($connection, $job)
    {
        $this->job = $job;
        return $connection;
    }
    
    public function pull($block = true)
    {
        if ($block) {
            $job = $this->consumer->brPop($this->job, $this->timeout); 
        } else {
            $job = $this->consumer->rPop($this->job);
        }
        return $job ? $this->unserialize($job[1]) : null;
    }

    public function consume(callable $call)
    {
        while (true) {
            if ($job = $this->consumer->brPop($this->job, $this->timeout)) {
                $message = $this->unserialize($job[1]);
                if ($call($message) === false) {
                    $this->consumer->lPush($this->job, $job[1]);
                }
            }
        }
    }
}
