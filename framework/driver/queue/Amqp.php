<?php
namespace framework\driver\queue;

/* 
 * http://pecl.php.net/package/amqp
 */
class Amqp extends Queue
{
    protected function connect()
    {
        $link = new \AMQPConnection($this->config);
        if ($link->connect()) {
            return $this->link = $link;
        }
        throw new \Exception('Can not connect to AMQP server');
    }

    public function __destruct()
    {
        if ($this->link) {
            $this->link->disconnect();
        }
    }
}
