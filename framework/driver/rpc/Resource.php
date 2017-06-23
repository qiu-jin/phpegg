<?php
namespace framework\driver\rpc;

use framework\core\http\Client;

class Resource
{
    protected $config;
    protected $requset_encode = 'body';
    protected $response_decode = 'body';
    
    public function __construct($config)
    {
        $this->config = $config;
        if (isset($config['requset_encode']) && in_array($config['requset_encode'], ['json', 'xml'])) {
            $this->requset_encode = $config['requset_encode'];
        }
        if (isset($config['response_decode']) && in_array($config['response_decode'], ['json', 'xml'])) {
            $this->response_decode = $config['response_decode'];
        }
    }
    
    public function __get($name)
    {
        return new query\Resource($this, $name);
    }
    
    public function __send($uri, $method, $data, $client_methods)
    {
        $client = new Client(strtoupper($method), $this->config['url'].'/'.$uri);
        isset($this->config['headers']) && $client->headers($this->config['headers']);
        isset($this->config['curlopt']) && $client->curlopt($this->config['curlopt']);
        if ($data) {
            $client->{$this->requset_encode}($data);
        }
        if ($client_methods) {
            foreach ($client_methods as $item) {
                $client->$item[0](...$item[1]);
            }
        }
        $status = $client->status;
        if ($status >= 200 && $status < 300) {
            return $client->{$this->response_decode};
        }
        return $status ? $this->__error($status) : $this->__error(...$client->error);
    }
    
    protected function __error($code, $message = null)
    {
        if ($this->throw_exception) {
            throw new \Exception("RPC error $code: $message");
        } else {
            return error("$code: $message");
        }
    }
}