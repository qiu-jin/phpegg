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
    protected $bind_params = true;
    protected $bind_params_name = [];
    
    const ALLOW_CLIENT_METHODS = null;
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        Loader::add($config['services']);
        isset($config['prefix']) && $this->prefix = $config['prefix'];
        isset($config['bind_params']) && $this->bind_params = $config['bind_params'];
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
            $this->rpc[$class] = new $class("$this->host:$this->port", ['credentials' => \Grpc\ChannelCredentials::createInsecure()]);
        }
        if ($params) {
            if ($this->bind_params) {
                $param = $this->__bindParams($class, $method, $params);
            } else {
                $param = $params[0];
            }
            list($reply, $status) = $this->rpc[$class]->$method($param)->wait();
        } else {
            list($reply, $status) = $this->rpc[$class]->$method()->wait();
        }
        return $reply;
    }
    
    protected function __bindParams($class, $method, $params)
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
}