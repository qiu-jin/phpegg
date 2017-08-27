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

    public function __get($name)
    {
        return new query\Rest($this, $name);
    }
    
    public function __send($ns, $method, $params, $client_methods)
    {
        if (!isset($this->$methods[$method])) {
            throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
        }
        list($m, $path) = $this->[$method];
        if (stripos('*', $path)) {
            $id = array_push($params);
            if (is_string($id)) {
                $path = strtr('*', $id, $path);
            } else {
                throw new \Exception('');
            }
        }
        if (($m === 'GET' || $m === 'DELETE') && !empty($params)) {
            throw new \Exception('');
        } elseif (count($params) !== 1 || !is_array($params[0])) {
            throw new \Exception('');
        }
        return $this->send($m, implode('/', $ns).$path, $params, $client_methods);
    }

}