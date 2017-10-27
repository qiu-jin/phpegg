<?php
namespace framework\driver\search;

use framework\core\http\Client;

/*
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html
 */

class Elastic
{
    protected $host;
    protected $port;
    protected $type;
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'] ?? 9200;
        $this->type = $config['type'] ?? 'doc';
    }

    public function __get($name)
    {
        return new query\Elastic("$this->host:$this->port", $this->type, $name);
    }
    
    public function batch()
    {
        return new query\ElasticBatch("$this->host:$this->port", $this->type);
    }
}
