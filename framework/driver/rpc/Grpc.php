<?php
namespace framework\driver\rpc;

use framework\core\Loader;

/* 
 * https://grpc.io/docs/quickstart/php.html
 */

class Grpc
{
    protected $rpc;
    protected $host;
    protected $port;
    protected $prefix;
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        Loader::add($config['services']);
        isset($config['prefix']) && $this->prefix = $config['prefix'];
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
            $this->rpc[$class] = new $class("$this->host:$this->port", ['credentials' => \Grpc\ChannelCredentials::createInsecure()]);
        }
        list($reply, $status) = $this->rpc[$class]->$method(...$params)->wait();
        return $reply;
    }
    
    /*
    protected function bindParams($class, $method, $params)
    {
        if (!isset($this->bind_params_name[$class][$method])) {
            $this->bind_params_name[$class][$method] = (new \ReflectionMethod($class, $method))->getParameters()[0]->getName();
        }
        $name = $this->bind_params_name[$class][$method];
        $name = $parameter->getName();
        $request = new $name();
        foreach ($params as $k => $v) {
            $request->{'set'.ucfirst($k)}($v);
        }
        return $request;
    }
    */
}