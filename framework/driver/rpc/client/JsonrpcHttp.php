<?php
namespace framework\driver\rpc\client;

use framework\core\http\Client;

class JsonrpcHttp
{
	// 配置项
    protected $config/* = [
        // 服务端点（HTTP）
        'endpoint'
		// HTTP请求headers（HTTP）
		'http_headers',
        // HTTP请求curl设置（HTTP）
        'http_curlopts'
    ]*/;
    
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
    public function send($data)
    {
        $client = Client::post($this->config['endpoint']);
        if (isset($this->config['http_headers'])) {
            $client->headers($this->config['http_headers']);
        }
        if (isset($this->config['http_curlopts'])) {
            $client->curlopts($this->config['http_curlopts']);
        }
        $client->body($this->config['requset_serialize']($data));
		$result = $client->response()->body;
        if ($result !== false) {
            return $this->config['response_unserialize']($result);
        }
        if ($error = $client->error) {
            error("-32000: Internet error [$error->code]$error->message");
        } else {
            error('-32603: nvalid JSON-RPC response');
        }
    }
}