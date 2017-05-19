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
    protected $struct_params_class;
    protected $reflect_struct_params = false;
    
    public function __construct($config)
    {
        try {
            $socket = new TSocket($config['host'], $config['port']);
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
        return $this->call(null, $method, $params);
    }
    
    public function call($ns, $method, $params = [])
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
        if ($params && $this->reflect_struct_params) {
            if (!isset($this->struct_params_class[$class][$method])) {
                $this->struct_params_class[$class][$method] = $this->reflectStructParams($this->rpc[$class], $method);
            }
            if ($this->struct_params_class[$class][$method]) {
                foreach ($this->struct_params_class[$class][$method] as $i => $param_class) {
                    $params[$i] = new $param_class($params[$i]);
                }
            }
        }
        return $this->rpc[$class]->$method(...$params);
    }
    
    protected function reflectStructParams($class, $method)
    {
        $struct_params_class = false;
        foreach ((new \ReflectionMethod($class, $method))->getParameters() as $i => $parameter) {
            if ($param_class = $parameter->getClass()) {
                $struct_params_class[$i] = $param_class->getName();
            }
        }
        return $struct_params_class;
    }
    
    public function __destruct()
    {
        $this->transport && $this->transport->close();
    }
}
