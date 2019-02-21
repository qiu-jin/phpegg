<?php
namespace framework\driver\rpc\client;

/*
 * https://grpc.io/docs/quickstart/php.html
 */
use Grpc\ChannelCredentials;

class Grpc
{
	// 配置项
    protected $config;
	// client实例
    protected $clients;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /*
     * 发送请求
     */
    public function send($service, $method, $message)
    {
        list($response_message, $status) = $this->getClient($service)->$method($message)->wait();
        if ($status->code === 0) {
            return $response_message;
        }
        error("[$status->code]$status->details");
    }
    
    /*
     * 获取client实例
     */
    protected function getClient($service)
    {
        if (isset($this->clients[$service])) {
            return $this->clients[$service];
        }
        $class = $service.'Client';
        $options = $this->config['grpc_options'] ?? null;
        if (!isset($options['credentials'])) {
            $options['credentials'] = ChannelCredentials::createInsecure();
        }
        return $this->clients[$service] = new $class($this->config['host'].':'.($this->config['port'] ?? 50051), $options);
    }
}