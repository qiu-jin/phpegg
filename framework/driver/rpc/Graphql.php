<?php
namespace framework\driver\rpc;

use framework\core\Logger;
use framework\core\http\Client;

class Graphql
{
	protected $config = [
		/*
        // 服务端点
        'endpoint'
        // 请求公共headers
        'http_headers'
        // 请求公共curlopts
        'http_curlopts'
		*/
		'debug'	=> \app\env\APP_DEBUG,
        // field方法别名
        'field_method_alias'    => 'field',
        // exec方法别名
        'exec_method_alias'		=> 'exec',
	];
	
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config + $this->config;
    }

    /*
     * query请求
     */
    public function query(...$params)
    {
		return new query\Graphql($this->config, '', 'query', $params, $this);
    }
	
    /*
     * mutation请求
     */
    public function mutation(...$params)
    {
		return new query\Graphql($this->config, '', 'mutation', $params, $this);
    }
	
    /*
     * 执行请求
     */
    public function exec($gql)
    {
		$client = Client::post($this->config['endpoint']);
        if (isset($this->config['http_headers'])) {
            $client->headers($this->config['http_headers']);
        }
        if (isset($this->config['http_curlopts'])) {
            $client->curlopts($this->config['http_curlopts']);
        }
		$response = $client->json(['query' => $gql])->response();
		if ($this->config['debug']) {
			$this->log($gql.PHP_EOL.$response->body);
		}
		if ($result = $response->json()) {
			if (isset($result['data'])) {
				return $result['data'];
			}
			if (isset($result['errors']['message'])) {
				error($result['errors']['message']);
			}
			error("无效响应: $result");
		}
        error($client->error);
    }
	
    /*
     * 日志处理
     */
    protected function log($log)
    {
		Logger::channel($this->config['debug'])->debug($log);
    }
}