<?php
namespace framework\driver\rpc\client;

/*
 * https://grpc.io/docs/quickstart/php.html
 */
use Grpc\ChannelCredentials;

class GrpcGoogle
{
    protected $config;
    protected $clients;
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    public function send($service, $method, $message)
    {
        list($response_message, $status) = $this->getClient($service)->$method($message)->wait();
        if ($status->code === 0) {
            return $response_message;
        }
        error("[$status->code]$status->details");
    }
    
    protected function getClient($service)
    {
        if (isset($this->clients[$service])) {
            return $this->clients[$service];
        }
        $class = $service.'Client';
        return $this->clients[$service] = new $class($this->config['host'].':'.($this->config['port'] ?? 50051), [
            'credentials' => ChannelCredentials::createInsecure()
        ]);
    }
}