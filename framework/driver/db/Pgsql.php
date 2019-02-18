<?php
namespace framework\driver\db;

class Pgsql extends Pdo
{
	// 构造器
    const BUILDER = builder\Pgsql::class;
    
    /*
     * 获取dsn
     */
    protected function getDsn($config)
    {
        $dsn = 'pgsql:host='.$config['host'].';dbname='.$config['dbname'];
        if (isset($config['port'])) {
            $dsn .= ';port='.$config['port'];
        }
        return $dsn;
    }
    
    /*
     * 获取表字段名
     */
    protected function getFields($table)
    {
		$fields = $this->select("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME = '$table'");
        return array_column($fields, 'column_name');
    }
}