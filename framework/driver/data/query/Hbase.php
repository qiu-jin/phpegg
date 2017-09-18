<?php
namespace framework\driver\data\query;

class Hbase
{
    protected $table;
    protected $thrift;
    
    public function __construct($thrift, $table)
    {
        $this->table = $table;
        $this->thrift = $thrift;
    }
    
    public function __call($method, $params)
    {
        return $this->thrift->$method($this->table, ...$params);
    }
    
    public function get($key, $option = null)
    {
        $option['row'] = $key;
        return $this->thrift->get($this->table, new \hbase\TGet($option));
    }
    
    public function scan($key, $option = null)
    {

    }
    
    public function put($key, $value, $option = null)
    {
        $option['row'] = $key;
        $option['columnValues'] = $value;
        return $this->thrift->put($this->table, new \hbase\TPut($option));
    }
    
    public function delete($key, $option = null)
    {
        $option['row'] = $key;
        return $this->thrift->deleteSingle($this->table, new \hbase\TDelete($option));
    }
}

