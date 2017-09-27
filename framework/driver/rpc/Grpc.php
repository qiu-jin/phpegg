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
    protected $param_mode = 1;
    protected $request_scheme_suffix = 'Request';
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        foreach ($config['service_schemes'] as $type => $scheme) {
            Loader::add($scheme, $type);
        }
        isset($config['prefix']) && $this->prefix = $config['prefix'];
        isset($config['param_mode']) && $this->param_mode = $config['param_mode'];
        isset($config['request_scheme_suffix']) && $this->request_scheme_suffix = $config['request_scheme_suffix'];
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
            $client = $class.'Client';
            $this->rpc[$class] = new $client("$this->host:$this->port", ['credentials' => \Grpc\ChannelCredentials::createInsecure()]);
        }
        if ($this->param_mode === 2) {
            $params = $this->bindParams($class.ucfirst($method).$this->request_scheme_suffix, $params);
        }
        list($reply, $status) = $this->rpc[$class]->$method($params)->wait();
        if ($status->code === 0) {
            return $reply;
        }
        error("$status->code: $status->details");
    }
    
    protected function bindParams($request_class, $params)
    {
        $i = 0;
        $request_object = new $request_class;
        foreach (get_class_methods($request_class) as $method) {
            if (substr($method, 0, 3) === 'set') {
                if (!isset($params[$i])) {
                    break;
                }
                $request_object->$method($params[$i]);
                $i++;
            }
        }
        return $request_object;
    }
    
    /*
    protected function getRequestClass($class, $method)
    {
        if (!isset($this->request_classes[$class][$method])) {
            $this->request_classes[$class][$method] = (new \ReflectionMethod($class, $method))->getParameters()[0]->getName();
        }
        return $this->request_classes[$class][$method];
    }
    */
}