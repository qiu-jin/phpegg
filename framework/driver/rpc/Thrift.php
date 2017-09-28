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
    protected $auto_bind_param = false;
    protected $service_method_params;
    
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
        foreach ($config['service_schemes'] as $type => $scheme) {
            Loader::add($scheme, $type);
        }
        isset($config['prefix']) && $this->prefix = $config['prefix'];
        isset($config['tmultiplexed']) && $this->tmultiplexed = $config['tmultiplexed'];
    }

    public function __get($name)
    {
        return $this->query($name);
    }

    public function __call($method, $params = [])
    {
        return $this->call(null, $method, $params);
    }
    
    public function query($name, $client_methods = null)
    {
        return new query\Query($this, $name, $client_methods);
    }
    
    public function call($ns, $method, $params, $client_methods)
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
        if ($this->auto_bind_param && $params) {
            $this->bindParams($class, $method, $params);
        }
        return $this->rpc[$class]->$method(...$params);
    }
    
    protected function bindParams($class, $method, &$params)
    {
        if (isset($this->service_method_params[$class][$method])) {
            if (empty($this->service_method_params[$class][$method])) {
                return;
            }
            foreach ($this->service_method_params[$class][$method] as $i => $name) {
               $params[$i] = new $name($params[$i]);
            }
        } else {
            $this->service_method_params[$class][$method] = [];
            $refs = (new \ReflectionMethod($class, $method))->getParameters();
            foreach ($refs as $i => $ref) {
                $type = (string) $ref->getType();
                if ($type === 'object') {
                    $name = $ref->getName();
                    $params[$i] = new $name($params[$i]);
                    $this->service_method_params[$class][$method][$i] = $name;
                }
            }
        }
    }
    
    public function __destruct()
    {
        $this->transport && $this->transport->close();
    }
}
