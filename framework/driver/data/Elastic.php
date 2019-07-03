<?php
namespace framework\driver\data;

/*
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html
 */
class Elastic
{
	// 服务端点
    protected $endpoint;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->endpoint = $config['host'].':'.($config['port'] ?? '9200');
    }

    /*
     * 魔术方法，索引实例
     */
    public function __get($name)
    {
        return $this->index($name);
    }
    
    /*
     * 索引实例
     */
    public function index($name, $type = null)
    {
        return new query\Elastic($this->endpoint, $name, $type ?? $this->type);
    }
    
    /*
     * 批量请求
     */
    public function batch($name = null, $type = null)
    {
        return new query\ElasticBatch($this->endpoint, $name, $type ?? $this->type);
    }
}
