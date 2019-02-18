<?php
namespace framework\driver\db;

class Oracle extends Pdo
{
	// 构造器
    const BUILDER = builder\Oracle::class;
    
    /*
     * 获取dsn
     */
    protected function getDsn($config)
    {
        $dsn = 'oci:dbname='.$config['dbname'].';host='.$config['host'];
        $dsn .= isset($config['port']) ? ':'.$config['port'] : '/';
        return $dsn;
    }
    
    /*
     * 获取表字段名
     */
    protected function getFields($table)
    {
        // 待测
        return array_column($this->select("describe \"$table\""), 'Field');
    }
}