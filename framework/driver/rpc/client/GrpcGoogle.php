<?php
namespace framework\driver\rpc\client;

class GrpcGoogle
{
    protected $config;
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    public function send($service, $method, $message)
    {
        $class = $service.'Client';
        $client = new $class($this->config['host'].':'.($this->config['port'] ?? 50051), [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);
        list($response_message, $status) = $client->$method($message)->wait();
        if ($status->code === 0) {
            return $response_message;
        }
        error("[$status->code]$status->details");
    }
}