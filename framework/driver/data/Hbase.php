<?php
namespace framework\driver\nosql;

use framework\driver\rpc\Thrift;

/*
 * https://github.com/apache/hbase/blob/master/hbase-thrift/src/main/resources/org/apache/hadoop/hbase/thrift2/hbase.thrift
 */
class Hbase extends Thrift
{
    public function __construct($config)
    {
        parent::__construct([
            'host'  => $config['host'],
            'port'  => $config['port'],
            'class' => ['hbase' => $config['idl_path']],
            'prefix'=> 'hbase\THBaseService',
        ]);
    }
    
    public function __get($name)
    {
        return new query\Hbase($this, $name);
    }
}
