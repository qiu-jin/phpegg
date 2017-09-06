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
    protected $bind_params = true;
    protected $bind_params_name = [];
    
    const ALLOW_CLIENT_METHODS = [];
    
    public function __construct($config)
    {
        $socket = new TSocket($config['host'], $config['port']);
        if (isset($config['send_timeout'])) {
            $socket->setRecvTimeout($config['send_timeout']);
        }
        if (isset($config['recv_timeout'])) {
            $socket->setRecvTimeout($config['recv_timeout']);
        }
        $this->transport = new TBufferedTransport($socket, 1024, 1024);
        $this->protocol  = new TBinaryProtocol($this->transport);
        $this->transport->open();
        Loader::add($config['services']);
        isset($config['types']) && Loader::add($config['types'], 'files');
        isset($config['prefix']) && $this->prefix = $config['prefix'];
        isset($config['bind_params']) && $this->bind_params = $config['bind_params'];
        isset($config['tmultiplexed']) && $this->tmultiplexed = $config['tmultiplexed'];
    }

    public function __get($class)
    {
        return new query\Query($this, $class);
    }

    public function __call($method, $params = [])
    {
        return $this->__send(null, $method, $params);
    }
    
    public function __send($ns, $method, $params = [])
    {
        $class = $this->prefix;
        if ($ns) {
            $class .= '\\'.implode('\\', $ns);
        }
        if (!isset($this->rpc[$class])) {
            if ($this->tmultiplexed) {
                $name = substr(strrchr($class, '\\'), 1);
                $this->rpc[$class] = new $class(new TMultiplexedProtocol($this->protocol, $name));
            } else {
                $this->rpc[$class] = new $class($this->protocol);
            }
        }
        if ($this->bind_params && $params) {
            $this->__bindParams($class, $method, $params);
        }
        return $this->rpc[$class]->$method(...$params);
    }
    
    protected function __bindParams($class, $method, &$params)
    {
        if (isset($this->bind_params_name[$class][$method])) {
            if (empty($this->bind_params_name[$class][$method])) {
                return;
            }
            foreach ($this->bind_params_name[$class][$method] as $i => $name) {
               $params[$i] = new $name($params[$i]);
            }
        } else {
            $this->bind_params_name[$class][$method] = [];
            $refs = (new \ReflectionMethod($class, $method))->getParameters();
            foreach ($refs as $i => $ref) {
                $type = (string) $ref->getType();
                if ($type === 'object') {
                    $name = $ref->getName();
                    $params[$i] = new $name($params[$i]);
                    $this->bind_params_name[$class][$method][$i] = $name;
                }
            }
        }
    }
    
    public function __destruct()
    {
        $this->transport && $this->transport->close();
    }
}
