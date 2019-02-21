<?php
namespace framework\driver\rpc\client;

use framework\core\http\Client;

class GrpcHttp
{
	// 配置项
    protected $config;
    
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
        $url    = $this->config['endpoint'].'/'.strtr($service, '\\', '.').'/'.$method;
        $data   = $message->serializeToString();
        $size   = strlen($data);
        $body   = pack('C1N1a'.$size, 0, $size, $data);
        $client = Client::post($url)->body($body);
        if (!empty($this->config['http_headers'])) {
            $client->headers($this->config['http_headers']);
        }
        if (!empty($this->config['http_curlopts'])) {
            $client->curlopts($this->config['http_curlopts']);
        }
        $response = $client->response();
        if (isset($response->headers['grpc-status'])) {
            if ($response->headers['grpc-status'] === '0') {
                $result = unpack('Cencode/Nzise/a*data', $response->body);
                if ($result['zise'] !== strlen($result['data'])) {
                    error('Invalid input');
                }
                $response_class = strtr($this->config['response_message_format'], [
                    '{service}' => $service,
                    '{method}'  => ucfirst($method)
                ]);
                $response_message = new $response_class;
                $response_message->mergeFromString($result['data']);
                return $response_message;
            }
            error("[{$response->headers['grpc-status']}]".$response->headers['grpc-message']);
        }
        error($client->error);
    }
}