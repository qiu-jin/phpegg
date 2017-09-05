<?php
namespace framework\driver\search;

use framework\core\http\Client;

class Elastic
{
    protected $host;
    protected $port;
    protected $indexes;
    protected $default_type = 'all';
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'] ?? 9200;
        if (isset($config['default_type'])) {
            $this->default_type = $default_type;
        }
    }
    
    public function ns($ns)
    {
        return new query\Elastic("$this->host:$this->port/$ns");
    }
    
    public function __get($name)
    {
        return new query\Elastic("$this->host:$this->port/$name/$this->default_type");
    }
    
    public function bulkRaw($query)
    {
        return Client::post("$this->host:$this->port")->json($query)->json;
    }
    
    public function getMultiRaw($query)
    {
        return Client::get("$this->host:$this->port/_mget")->json($query)->json;
    }
}
