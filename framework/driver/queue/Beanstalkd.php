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
        $link = new Pheanstalk($this->config['host'], '11300', isset($this->config['timeout']) ? $this->config['timeout'] : 3);
        return $this->link = $link;
    }

    public function __destruct()
    {
        if ($this->link) {
            //$this->link->disconnect();
        }
    }
}
