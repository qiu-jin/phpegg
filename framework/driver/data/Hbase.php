<?php
namespace framework\driver\data;

use framework\driver\rpc\Thrift;

/*
 * https://github.com/apache/hbase/blob/master/hbase-thrift/src/main/resources/org/apache/hadoop/hbase/thrift2/hbase.thrift
 */

class Hbase extends Thrift
{
    public function __construct($config)
    {
        $config['prefix'] = 'Hbase\THBaseService';
        $config['bind_params'] = false;
        parent::__construct($config);
    }
    
    public function __get($name)
    {
        return new query\Hbase($this, $name);
    }
    
    public function __call($name)
    {
        throw new \Exception('Call to undefined method '.__CLASS__.'::'.$name);
    }
}
