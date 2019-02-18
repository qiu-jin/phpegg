<?php
namespace framework\driver\db;

class Mysql extends Pdo
{
	// 构造器
    const BUILDER = builder\Builder::class;
    
    /*
     * 获取dsn
     */
    protected function getDsn($config)
    {
        $dsn = 'mysql:dbname='.$config['dbname'];
        if (isset($config['host'])) {
            $dsn .= ';host='.$config['host'];
            if (isset($config['port'])) {
                $dsn .= ';port='.$config['port'];
            }
        } elseif (isset($config['socket'])) {
            $dsn .= ';unix_socket='.$config['socket'];
        }
        if (isset($config['charset'])) {
            $dsn .= ';charset='.$config['charset'];
        }
        return $dsn;
    }
    
    /*
     * 获取表字段名
     */
    protected function getFields($table)
    {
        return array_column($this->select("desc `$table`"), 'Field');
    }
}
