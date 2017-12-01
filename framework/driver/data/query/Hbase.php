<?php
namespace framework\driver\data\query;

class Hbase
{
    protected $rpc;
    protected $table;
    
    public function __construct($rpc, $table)
    {
        $this->rpc = $rpc;
        $this->table = $table;
    }
    
    public function __call($method, $params)
    {
        return $this->rpc->$method($this->table, ...$params);
    }
    
    public function get($key, $options = null)
    {
        $options['row'] = $key;
        return $this->rpc->get($this->table, new \hbase\TGet($options));
    }
    
    public function scan($key, $options = null)
    {
        
    }
    
    public function put($key, $value, $options = null)
    {
        $options['row'] = $key;
        $options['columnValues'] = $value;
        return $this->rpc->put($this->table, new \hbase\TPut($options));
    }
    
    public function delete($key, $options = null)
    {
        $option['row'] = $key;
        return $this->rpc->deleteSingle($this->table, new \hbase\TDelete($options));
    }
}
