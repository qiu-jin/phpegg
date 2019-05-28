<?php
namespace framework\driver\rpc\query;

use framework\core\http\Client;
use framework\driver\rpc\Rest as Restrpc;

class RestBatch
{
	// namespace
    protected $ns;
	// 配置项
    protected $config;
	// client实例
    protected $client;
	// 请求集合
    protected $queries;
	// filter设置
    protected $filters;
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
        return $this->ns($name);
    }
    
    /*
     * 设置namespace
     */
    public function ns($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    /*
     * 设置构建处理器
     */
    public function build(callable $handler)
    {
        $this->build_handler = $handler;
        return $this;
    }
    
    /*
     * 设置filter
     */
    public function filter(...$params)
    {
        $this->filters[] = $params;
    }
    
    /*
     * 魔术方法，调用rpc方法
     */
    public function __call($method, $params)
    {
        if (in_array($m = strtoupper($method), Restrpc::ALLOW_HTTP_METHODS)) {
            $this->queries[] = $this->buildQuery($m, $params);
            return $this;
        }
        throw new \Exception('Call to undefined method '.__CLASS__."::$method");
    }
    
    /*
     * 调用
     */
    public function call(callable $handler = null)
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
    protected function buildQuery($method, $params)
    {
        $client = $this->client->make($method, $this->ns ?? [], $this->filters, $params);
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