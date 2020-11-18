<?php
namespace framework\driver\search;

/*
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html
 */
class Elastic
{
	// 配置
    protected $config;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config;
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
    public function index($name)
    {
        return new query\Elastic($name, $this->config);
    }
    
    /*
     * 批量请求
     */
    public function batch($name = null)
    {
        return new query\ElasticBatch($name, $this->config);
    }
}
