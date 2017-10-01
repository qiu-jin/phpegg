<?php
namespace framework\driver\db;

class Oracle extends Pdo
{
    const BUILDER = builder\Oracle::class;
    
    protected function dsn($config)
    {
        $dsn = 'oci:dbname='.$config['dbname'].';host='.$config['host'];
        $dsn .= isset($config['port']) ? ':'.$config['port'] : '/';
        return $dsn;
    }
    
    protected function getFields($table)
    {
        //return array_column($this->exec("describe `$table`"), 'Field');
    }
}