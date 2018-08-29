<?php
namespace framework\driver\rpc\client;

use framework\core\http\Client;

class JsonrpcHttp
{
    protected $config;
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function send($data)
    {
        $client = Client::post($this->config['endpoint']);
        if (isset($this->config['headers'])) {
            $client->headers($this->config['headers']);
        }
        if (isset($this->config['curlopts'])) {
            $client->curlopts($this->config['curlopts']);
        }
        $client->body($this->config['requset_serialize']($data));
        if (($result = $client->response->body) !== false) {
            return $this->config['response_unserialize']($result);
        }
        if ($error = $client->error) {
            error("-32000: Internet error [$error->code]$error->message");
        } else {
            error('-32603: nvalid JSON-RPC response');
        }
    }
}