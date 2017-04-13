<?php
namespace framework\driver\queue;

/* 
 * http://pecl.php.net/package/amqp
 */

class Amqp extends Queue
{
    protected function connect($mode)
    {
        $link = new \AMQPConnection($this->config);
        if ($link->connect()) {
            $channel = new \AMQPChannel($link);
            $exchange = new \AMQPExchange($channel);
            $exchange->setType(AMQP_EX_TYPE_DIRECT);
            $exchange->setFlags(AMQP_DURABLE);
            $this->link = $link;
            return $exchange;
        }
    }

    public function __destruct()
    {
        if ($this->link) {
            $this->link->disconnect();
        }
    }
}
