<?php
namespace framework\driver\rpc;

use framework\core\Loader;

use Thrift\Transport\TSocket;
use Thrift\Transport\TBufferedTransport;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Protocol\TMultiplexedProtocol;

/* 
 * composer require apache/thrift
 * https://github.com/apache/thrift
 */
class Thrift
{
    protected $rpc;
    protected $prefix;
    protected $protocol;
    protected $transport;
    protected $tmultiplexed = false;
    
    public function __construct($config)
    {
        try {
            $socket = new TSocket($config['host'], $config['port']);
            if (isset($config['send_timeout'])) {
                $socket->setRecvTimeout($config['send_timeout']);
            }
            if (isset($config['recv_timeout'])) {
                $socket->setRecvTimeout($config['recv_timeout']);
            }
            $this->ransport = new TBufferedTransport($socket, 1024, 1024);
            $this->protocol = new TBinaryProtocol($this->ransport);
            $this->ransport->open();
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        isset($config['class']) && Loader::add($config['class']);
        isset($config['prefix']) && $this->prefix = $config['prefix'];
    }

    public function __get($class)
    {
        return new query\Names($this, $class);
    }

    public function __call($method, $params = [])
    {
        return $this->__send(null, $method, $params);
    }
    
    public function __send($ns, $method, $params = [])
    {
        $class = $ns ? $this->prefix.'\\'.implode('\\', $ns) : $this->prefix;
        if (!isset($this->rpc[$class])) {
            if ($this->tmultiplexed) {
                $name = substr(strrchr($class, '\\'), 1);
                $this->rpc[$class] = new $class(new TMultiplexedProtocol($this->protocol, $name));
            } else {
                $this->rpc[$class] = new $class($this->protocol);
            }
        }
        return $this->rpc[$class]->$method(...$params);
    }
    
    public function __destruct()
    {
        $this->transport && $this->transport->close();
    }
}
