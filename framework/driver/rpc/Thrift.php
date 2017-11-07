<?php
namespace framework\driver\rpc;

use framework\core\Loader;

/* 
 * composer require apache/thrift
 * https://github.com/apache/thrift
 */
use Thrift\Transport\TSocket;
use Thrift\Transport\TBufferedTransport;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Protocol\TMultiplexedProtocol;

class Thrift
{
    protected $rpc;
    protected $prefix;
    protected $protocol;
    protected $transport;
    protected $tmultiplexed;
    protected $auto_bind_param;
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
        $this->prefix = $config['prefix'] ?? null;
        $this->tmultiplexed = $config['tmultiplexed'] ?? false;
        $this->auto_bind_param = $config['auto_bind_param'] ?? false;
    }

    public function __get($name)
    {
        return $this->query($name);
    }

    public function __call($method, $params)
    {
        return $this->query()->$method(...$params);
    }
    
    public function query($name = null, $client_methods = null)
    {
        return new query\Query($this, $name, $client_methods);
    }
    
    public function call($ns, $method, $params, $client_methods)
    {
        if (isset($this->prefix)) {
            array_unshift($ns, $this->prefix);
        }
        if (!$ns) {
            throw new \Exception('service is empty');
        }
        $class = implode('\\', $ns);
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
