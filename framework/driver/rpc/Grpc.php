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
    protected $request_classes;
    protected $auto_bind_param = false;
    protected $request_scheme_format;//'{service}{method}Request'
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        foreach ($config['service_schemes'] as $type => $scheme) {
            Loader::add($scheme, $type);
        }
        isset($config['prefix']) && $this->prefix = $config['prefix'];
        isset($config['auto_bind_param']) && $this->auto_bind_param = $config['auto_bind_param'];
        isset($config['request_scheme_format']) && $this->request_scheme_format = $config['request_scheme_format'];
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
        if ($this->auto_bind_param) {
            if ($this->request_scheme_format) {
                $request_class = strtr($this->request_scheme_format, ['{service}' => $class, '{method}' => ucfirst($method)]);
            } else {
                $request_class = $this->getRequestClass($client, $method);
            }
            $params = $this->bindParams($request_class, $params);
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
    
    protected function getRequestClass($class, $method)
    {
        if (!isset($this->request_classes[$class][$method])) {
            $this->request_classes[$class][$method] = (new \ReflectionMethod($class, $method))->getParameters()[0]->getName();
        }
        return $this->request_classes[$class][$method];
    }
}