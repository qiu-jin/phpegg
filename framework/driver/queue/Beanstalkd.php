<?php
namespace framework\driver\queue;

/* 
 * composer require pda/pheanstalk
 * https://github.com/pda/pheanstalk
 */

class Beanstalkd extends Queue
{
    protected function connect()
    {
        $link = new \Beanstalk();
        $link->addserver($config['host'], $config['port']);
        return $this->link = $link;
    }

    public function __destruct()
    {
        if ($this->link) {
            $this->link->disconnect();
        }
    }
}
