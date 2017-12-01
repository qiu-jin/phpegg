<?php
namespace framework\driver\queue;

/* 
 * composer require pda/pheanstalk
 * https://github.com/pda/pheanstalk
 */
use Pheanstalk\Pheanstalk;

class Beanstalkd extends Queue
{
    protected function connect()
    {
        return $this->link = new Pheanstalk($this->config['host'], $this->config['port'] ?? 11300, $this->config['timeout'] ?? 3);
    }

    public function __destruct()
    {
        if ($this->link->getConnection()->isServiceListening()) {
            $this->link->getConnection()->disconnect();
        }
    }
}
