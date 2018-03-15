<?php
namespace framework\driver\queue;

/* 
 * http://pecl.php.net/package/amqp
 */
class Amqp extends Queue
{
    protected function connect()
    {
        $connection = new \AMQPConnection($this->config);
        if ($connection->connect()) {
            return $connection;
        }
        throw new \Exception('Can not connect to AMQP server');
    }

    public function __destruct()
    {
        empty($this->connection) || $this->connection->disconnect();
    }
}
