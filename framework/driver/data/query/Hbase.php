<?php
namespace framework\driver\data\query;

class Hbase
{
    protected $rpc;
    protected $table;
    protected static $method_params_type = [
        'get'       => 'hbase\TGet',
        'exists'    => 'hbase\TGet',
        'put'       => 'hbase\TPut',
        'delete'    => 'hbase\TDelete',
    ];
    
    public function __construct($rpc, $table)
    {
        $this->rpc = $rpc;
        $this->table = $table;
    }
    
    public function __call($name, array $params = [])
    {
        if (isset(self::$method_params_type[$name])) {
            $params[] = (new self::$method_params_type[$name])(array_pop($params));
        }
        array_unshift($params, $this->table);
        return $this->rpc->__send(null, $name, $params);
    }
    
    /*
    public function get($key, $option = [])
    {
        $option['row'] = $key;
        if (isset($option['timeRange'])) {
            $option['timeRange'] = new \hbase\TTimeRange($option['timeRange']);
        }
        return $this->call('get', [$this->table, new \hbase\TGet($option)]);
    }
    
    public function exists($key, $option = [])
    {
        $option['row'] = $key;
        if (isset($option['timeRange'])) {
            $option['timeRange'] = new \hbase\TTimeRange($option['timeRange']);
        }
        return $this->call('exists', [$this->table, new \hbase\TGet($option)]);
    }
    
    public function put($key, $value, $option = [])
    {
        $option['row'] = $key;
        $option['columnValues'] = $value;
        if (isset($option['cellVisibility'])) {
            $option['cellVisibility'] = new \hbase\TCellVisibility($option['cellVisibility']);
        }
        return $this->call('put', [$this->table, new \hbase\TPut($option)]);
    }
    
    public function append($key, $value, $option = [])
    {

    }
    
    public function increment($key, $value, $option = [])
    {

    }
    
    public function delete($key, $option = [])
    {
        $option['row'] = $key;
        return $this->call('deleteSingle', [$this->table, new \hbase\TDelete($option)]);
    }
    
    public function getMultiple(array $params)
    {
        foreach ($params as $param) {
            if (isset($param['timeRange'])) {
                $param['timeRange'] = new \hbase\TTimeRange($param['timeRange']);
            }
            $data[] = new \hbase\TGet($param);
        }
        return $this->call('getMultiple', [$this->table, $data]);
    }
    
    public function existsAll(array $params)
    {
        foreach ($params as $param) {
            if (isset($param['timeRange'])) {
                $param['timeRange'] = new \hbase\TTimeRange($param['timeRange']);
            }
            $data[] = new \hbase\TGet($param);
        }
        return $this->call('existsAll', [$this->table, $data]);
    }
    
    public function putMultiple(array $params)
    {
        foreach ($params as $param) {
            if (isset($param['cellVisibility'])) {
                $param['cellVisibility'] = new \hbase\TCellVisibility($param['cellVisibility']);
            }
            $data[] = new \hbase\TPut($param);
        }
        return $this->call('putMultiple', [$this->table, $data]);
    }
    
    public function deleteMultiple(array $params)
    {
        foreach ($params as $param) {
            $data[] = new \hbase\TPut($param);
        }
        return $this->call('deleteMultiple', [$this->table, $data]);
    }
    
    public function checkAndPut()
    {

    }
    
    public function checkAndDelete()
    {

    }
    
    public function deleteMultiple()
    {

    }
    
    public function openScanner()
    {

    }
    
    public function getScannerRows()
    {

    }
    
    public function closeScanner()
    {

    }
    
    public function mutateRow()
    {

    }
    
    public function getScannerResults()
    {

    }
    
    public function getRegionLocation()
    {

    }
    
    public function getAllRegionLocations()
    {

    }
    
    public function checkAndMutate()
    {

    }
    
    protected function call($method, $params = null)
    {
        return $this->rpc->__send(null, $method, $params);
    }
    */
}

