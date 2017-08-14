<?php
namespace framework\driver\rpc;

use framework\core\http\Client;

class Rest
{
    protected $config = [
        'requset_encode' => 'body',
        'response_decode' => 'body',
    ];
    
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    public function __get($name)
    {
        return new query\Rest($this, $name);
    }
    
    public function __send($uri, $method, $data, $client_methods)
    {
        $client = new Client(strtoupper($method), $this->config['host'].'/'.$uri);
        if (isset($this->config['headers'])) {
            $client->headers($this->config['headers']);
        }
        if (isset($this->config['curlopt'])) {
            $client->headers($this->curlopt['curlopt']);
        }
        if ($client_methods) {
            foreach ($client_methods as $item) {
                $client->{$item[0]}(...$item[1]);
            }
        }
        if ($data) {
            $client->{$this->config['requset_encode']}($data);
        }
        $status = $client->status;
        if ($status >= 200 && $status < 300) {
            return $client->{$this->config['response_decode']};
        }
        return $status ? $this->__error($status) : $this->__error(...$client->error);
    }
    
    protected function __error($code, $message = null)
    {
        if (empty($this->config['throw_exception'])) {
            return error("$code: $message");
        } else {
            throw new \Exception("RPC error $code: $message");
        }
    }
}