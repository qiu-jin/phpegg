<?php
namespace framework\driver\rpc;

class Rest extends Http
{
    protected $methods = [
        'index'     => ['GET', '/'],
        'new'       => ['GET', '/create'],
        'create'    => ['POST', '/'],
        'show'      => ['GET', '/*'],
        'edit'      => ['GET', '/*/edit'],
        'update'    => ['PUT', '/*'],
        'destroy'   => ['DELETE', '/*']
    ];
    
    public function __construct($config)
    {
        if (isset($config['methods'])) {
            $this->methods = $config['methods'];
        }
        $this->config = array_merge($this->config, $config);
    }

    public function __get($name)
    {
        return new query\Rest($this, $name);
    }
    
    public function __send($ns, $method, $params, $client_methods)
    {
        if (!isset($this->methods[$method])) {
            throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
        }
        list($http_method, $path) = $this->methods[$method];
        if (stripos('*', $path)) {
            if (!$params) {
                throw new \Exception('missing parameter');
            }
            $path = strtr('*', array_push($params), $path);
        }
        return $this->send($http_method, implode('/', $ns).$path, $params, $client_methods);
    }

}