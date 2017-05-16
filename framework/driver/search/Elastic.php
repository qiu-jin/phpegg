<?php
namespace framework\driver\search;

use framework\util\Arr;
use framework\core\http\Client;

class Elastic extends Search;
{
    protected $host;
    protected $port;
    protected $type = 'default';
    protected $index;
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = isset($config['port']) ? $config['port'] : 9200;
    }
    
    public function get($id)
    {
        return $this->send('GET', $id);
    }
    
    public function select($data)
    {
        if (isset($data['id'])) {
            $id = Arr::pull($data, 'id');
            return $this->send('PUT', $id, $data, $index, $type);
        } else {
            return $this->send('POST', null, $data, $index, $type);
        }
    }
    
    public function update($id, $data)
    {
        return $this->send('PUT',$id, $data, $index, $type);
    }
    
    public function delete($id)
    {
        return $this->send('DELETE', $id, null, $index, $type);
    }
    
    public function search($query)
    {
        return $this->send('POST', '_search', $query, $index, $type);
    }
    
    protected function send($method, $query, $body, $index, $type)
    {
        $url = "$this->host:$this->port/$index/$type/$query";
        $result = Client::send($method, $this->host.':'.$this->port.$path, json_encode($body));
    }
    
    protected function build($query)
    {
        
    }
}
