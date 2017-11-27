<?php
namespace framework\driver\search;

/*
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html
 */
class Elastic
{
    protected $type;
    protected $endpoint;
    
    public function __construct($config)
    {
        $this->type = $config['type'] ?? 'doc';
        $this->endpoint = $config['host'].':'.($config['port'] ?? '9200');
    }

    public function __get($name)
    {
        return $this->index($name);
    }
    
    public function index($name, $type = null)
    {
        return new query\Elastic($this->endpoint, $name, $type ?? $this->type);
    }
    
    public function batch($name = null, $type = null)
    {
        return new query\ElasticBatch($this->endpoint, $name, $type ?? $this->type);
    }
}
