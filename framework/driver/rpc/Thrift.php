<?php
namespace framework\driver\rpc;

use framework\core\Loader;
use framework\extend\rpc\Names;

/* 
 * composer require apache/thrift
 * https://github.com/apache/thrift
 */
use Thrift\TMultiplexedProtocol;
use Thrift\Transport\TSocket;
use Thrift\Transport\THttpClient;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;
use Thrift\Protocol\TBinaryProtocol;

class Thrift
{
    private $rpc;
    private $protocol;
    private $transport;
    private $method_params_type = [];
    
    public function __construct($config)
    {
        if ($config['class']) {
            foreach ($config['class'] as $class) {
                $psr4[] = $class;
            }
            Loader::add($psr4);
        } else {
            throw new \Exception('class is empty');
        }
        try {
            $socket = new TSocket($config['host'], $config['port']);
            $this->ransport = new TBufferedTransport($socket, 1024, 1024);
            $protocol = new TBinaryProtocol($this->ransport);
            $this->ransport->open();
        } catch(\Exception $e) {
            throw new \Exception($e->message());
        }
    }

    public function __get($class)
    {
        return new Names($this, $class);
    }

    public function __call($method, $params = [])
    {
        return $this->call(null, $method, $params);
    }
    
    public function call($ns, $method, $params = [])
    {
        if (!isset($this->rpc[$ns])) {
            $class = $this->_className($ns);
            $this->rpc[$ns] = new $class(new TMultiplexedProtocol($this->protocol, $client));
        }
        $this->_bindParams($class, $method, $params);
        return $this->rpc[$ns]->$method(...$params);
    }
    
    
    private function _className($ns)
    {
        return '\\'.str_replace('.', '\\', $ns);
    }
    
    private function _bindParams($class, $method, &$params)
    {
        if (isset($this->method_params_type[$class][$method])) {
            foreach ($this->method_params_type[$class][$method] as $i => $type) {
                if ($type === 'object') {
                    $paramclass = $parameter->getName();
                    $params[$i] = new $paramclass($params[$i]);
                }
            }
        } else {
            $parameters = (new \ReflectionMethod($class, $method))->getParameters();
            foreach ($parameters as $i => $parameter) {
                $type = (string) $parameter->getType();
                if ($type === 'object') {
                    $paramclass = $parameter->getName();
                    $params[$i] = new $paramclass($params[$i]);
                }
                $this->method_params_type[$class][$method][] = $type;
            }
        }
    }
    
    public function __destruct()
    {
        $this->transport->close();
    }
}
