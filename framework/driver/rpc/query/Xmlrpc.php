<?php
namespace framework\driver\rpc\query;

use framework\App;
use framework\core\http\Client;

class Xmlrpc
{
    protected $ns;
    protected $config;
    
    public function __construct($name, $config)
    {
        if (isset($name)) {
            $this->ns[] = $name;
        }
        $this->config = $config;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        $this->ns[] = $method;
        return $this->call($params);
    }
    
    protected function call($params)
    {
        $client = Client::post($this->config['endpoint']);
        $client->header('User-Agent', 'phpegg'.App::VERSION);
        $client->header('Content-Type', 'text/xml');
        if (isset($this->config['http_headers'])) {
            $client->headers($this->config['http_headers']);
        }
        if (isset($this->config['http_curlopts'])) {
            $client->curlopts($this->config['http_curlopts']);
        }
        $client->body(xmlrpc_encode_request(implode('.', $this->ns), $params));
        if (($result = $client->response->body) !== false) {
            if ($result = xmlrpc_decode($result)) {
                if (!xmlrpc_is_fault($result)) {
                    return $result;
                }
                error($result['faultCode'].': '.$result['faultString']);
            }
        }
        error('Xmlrpc error: Invalid response');
    }
}