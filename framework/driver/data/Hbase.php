<?php
namespace framework\driver\data;

use framework\driver\rpc\Thrift;

/*
 * https://github.com/apache/hbase/blob/master/hbase-thrift/src/main/resources/org/apache/hadoop/hbase/thrift2/hbase.thrift
 */

class Hbase
{
    protected $rpc;
    
    public function __construct($config)
    {
        $config['prefix'] = 'Hbase\THBaseService';
        $config['bind_params'] = false;
        $this->rpc = new Thrift($config);
    }
    
    public function __get($name)
    {
        return $this->table($name);
    }
    
    public function table($name)
    {
        return new query\Hbase($this->rpc, $name);
    }
    
    public function __call($method, $params)
    {
        return $this->rpc->$method(...$params);
    }
}
