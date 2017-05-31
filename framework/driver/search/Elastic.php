<?php
namespace framework\driver\search;

use framework\core\http\Client;

class Elastic extends Search;
{
    protected $host;
    protected $port;
    protected $type = 'default';
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = isset($config['port']) ? $config['port'] : 9200;
    }
    
    public function send($method, $query, $body, $index, $type)
    {
        $url = "$this->host:$this->port/$index/$type/$query";
        $result = Client::send($method, $this->host.':'.$this->port.$path, json_encode($body));
    }
    
    protected function build($query)
    {
        
    }
}
