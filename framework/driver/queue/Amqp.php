<?php
namespace framework\driver\queue;

/* 
 * http://pecl.php.net/package/amqp
 */
class Amqp extends Queue
{
    protected function connect()
    {
        $this->connection = new \AMQPConnection($this->config);
        if (!$this->connection->connect()) {
            throw new \Exception('Can not connect to AMQP server');
        }
        return $this->connection;
    }

    public function __destruct()
    {
        empty($this->connection) || $this->connection->disconnect();
    }
}
