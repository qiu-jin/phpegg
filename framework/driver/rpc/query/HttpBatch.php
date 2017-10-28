<?php
namespace framework\driver\rpc\query;

use framework\core\http\Client;
use framework\driver\rpc\Http as RPC;

class HttpBatch
{
    protected $ns;
    protected $queries;
    protected $options;
    protected $client_methods;
    
    public function __construct($options)
    {
        $this->options = $options;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        switch ($method) {
            case $this->call_method:
                return $this->call();
            case $this->ns_method:
                $this->ns[] = $params[0];
                return $this;
            default:
                if (in_array($method, Http::ALLOW_CLIENT_METHODS, true)) {
                    $this->client_methods[] = [$method, $params];
                } else {
                    $this->queries[] = [$this->ns, $method, $params, $this->client_methods];
                    $this->ns = null;
                    $this->client_methods = null;
                }
                return $this;
        }
    }
    
    protected function call()
    {
        $mh = curl_multi_init();
        
    }
}