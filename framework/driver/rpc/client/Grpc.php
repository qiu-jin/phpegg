<?php
namespace framework\driver\rpc\client;

/*
 * https://grpc.io/docs/quickstart/php.html
 */
use Grpc\ChannelCredentials;

class Grpc
{
	// 配置项
    protected $config/* = [
        // 服务主机（GRPC）
        'host'
        // 服务端口（GRPC）
        'port'
        // grpc设置（GRPC）
        'grpc_options'
        // service类名前缀
        'service_prefix'
        // schema定义文件加载规则
        'schema_loader_rules'
    ] */;
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
		if (isset($this->config['service_prefix'])) {
			$service = $this->config['service_prefix'].'\\'.$service;
		}
		$client = $this->getClient($service);
		if (is_array($message)) {
			$json_msg = json_encode($message);
			$reflection = new \ReflectionMethod($client, $method);
			$parameters = $reflection->getParameters();
            $message = $parameters[0]->getClass()->newInstance();
            $message->mergeFromJsonString($json_msg);
		}
        list($reply, $status) = $client->$method($message)->wait();
        if ($status->code === 0) {
            return $reply->getMessage();
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