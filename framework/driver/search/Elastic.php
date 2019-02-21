<?php
namespace framework\driver\search;

/*
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html
 */
class Elastic
{
	// 类型(7.x版本中去除)
    protected $type = '_doc';
	// 服务端点
    protected $endpoint;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        if (isset($config['type'])) {
            $this->type = $config['type'];
        }
        $this->endpoint = $config['host'].':'.($config['port'] ?? '9200');
    }

    /*
     * 魔术方法，query实例
     */
    public function __get($name)
    {
        return $this->index($name);
    }
    
    /*
     * query实例
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
