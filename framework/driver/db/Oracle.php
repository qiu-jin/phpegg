<?php
namespace framework\driver\db;

class Oracle extends Pdo
{
    const BUILDER = builder\Oracle::class;
    
    protected function getDsn($config)
    {
        $dsn = 'oci:dbname='.$config['dbname'].';host='.$config['host'];
        $dsn .= isset($config['port']) ? ':'.$config['port'] : '/';
        return $dsn;
    }
    
    protected function getFields($table)
    {
        // 待测
        return array_column($this->exec("describe \"$table\""), 'Field');
    }
}