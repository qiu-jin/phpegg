<?php
namespace framework\driver\rpc\query;

use framework\core\http\Client;

class HttpBatch
{
	// namespace
    protected $ns;
	// 配置项
    protected $config;
	// client实例
    protected $client;
	// filter设置
    protected $filters;
	// 请求集合
    protected $queries;
	// 公共namespace
    protected $common_ns;
	// 构建处理器
    protected $build_handler;
	// 公共构建处理器
    protected $common_build_handler;
    
    /*
     * 构造函数
     */
    public function __construct($client, $common_ns, $config, $common_build_handler)
    {
        $this->client = $client;
        $this->config = $config;
        if (isset($common_ns)) {
            $this->ns[] = $this->common_ns[] = $common_ns;
        }
        $this->common_build_handler = $common_build_handler;
    }

    /*
     * 魔术方法，设置namespace
     */
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    /*
     * 魔术方法，调用rpc方法
     */
    public function __call($method, $params)
    {
        switch ($m = strtolower($method)) {
            case $this->config['batch_call_method_alias'] ?? 'call':
                return $this->call(...$params);
            case $this->config['filter_method_alias'] ?? 'filter':
                $this->filters[] = $params;
                return $this;
            case $this->config['ns_method_alias'] ?? 'ns':
                $this->ns[] = $params[0];
                return $this;
            case $this->config['build_method_alias'] ?? 'build':
                $this->build_handler = $params[0];
                return $this;
            default:
                $this->ns[] = $m;
                $this->queries[] = $this->buildQuery($params);
                return $this;
        }
    }
    
    /*
     * 调用
     */
    protected function call(callable $handler = null)
    {
        return Client::batch(
            $this->queries,
            $handler ?? [$this->client, 'response'],
            $this->config['batch_select_timeout'] ?? 0.1
        );
    }
    
    /*
     * 构建请求
     */
    protected function buildQuery($params)
    {
        $method = $params && is_array(end($params)) ? 'POST' : 'GET';
        $client = $this->client->make($method, $this->ns, $this->filters, $params);
        if (isset($this->common_build_handler)) {
            $this->common_build_handler($client);
        }
        if (isset($this->build_handler)) {
            $this->build_handler($client);
            $this->build_handler = null;
        }
        $this->ns = $this->common_ns;
        $this->filters = null;
        return $client;
    }
    
}