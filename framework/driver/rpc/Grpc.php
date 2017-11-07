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
    protected $auto_bind_param;
    // {service}{method}Request
    protected $request_scheme_format;
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        foreach ($config['service_schemes'] as $type => $scheme) {
            Loader::add($scheme, $type);
        }
        $this->prefix = $config['prefix'] ?? null;
        $this->auto_bind_param = $config['auto_bind_param'] ?? false;
        $this->request_scheme_format = $config['request_scheme_format'] ?? null;
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
            $client = $class.'Client';
            $this->rpc[$class] = new $client("$this->host:$this->port", [
                'credentials' => \Grpc\ChannelCredentials::createInsecure()
            ]);
        }
        if ($this->auto_bind_param) {
            if ($this->request_scheme_format) {
                $request_class = strtr($this->request_scheme_format, [
                    '{service}' => $class,
                    '{method}'  => ucfirst($method)
                ]);
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
            $this->request_classes[$class][$method] = (string) (new \ReflectionMethod($class, $method))->getParameters()[0]->getType();
        }
        return $this->request_classes[$class][$method];
    }
}