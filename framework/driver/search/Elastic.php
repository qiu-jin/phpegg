<?php
namespace framework\driver\search;

use framework\core\http\Client;

class Elastic extends Search;
{
    protected $host;
    protected $port;
    protected $index;
    protected $type = 'default';
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = isset($config['port']) ? $config['port'] : '9200';
    }
    
    public function type($type)
    {
        return $this->send('GET', $id, null, $index, $type);
    }
    
    public function get($id)
    {
        return $this->send('GET', $id, null, $index, $type);
    }
    
    public function select($data, $type = null, $index = null)
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            return $this->send('PUT', $id, $data, $index, $type);
        } else {
            return $this->send('POST', null, $data, $index, $type);
        }
    }
    
    public function update($id, $data, $type = null, $index = null)
    {
        return $this->send('PUT',$id, $data, $index, $type);
    }
    
    public function delete($id, $type = null, $index = null)
    {
        return $this->send('DELETE', $id, null, $index, $type);
    }
    
    public function search($query, $type = null, $index = null)
    {
        return $this->send('POST', '_search', $query, $index, $type);
    }
    
    private function send($method, $query, $body, $index, $type)
    {
        $url = "$this->host:$this->port/$index/$type/$query";
        
        
        return \Util\Http::request_json($this->host.':'.$this->port.$path, json_encode($body), null, $method);
    }
    
    public function build($query)
    {
        
    }
}
