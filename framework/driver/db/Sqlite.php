<?php
namespace framework\driver\db;

class Sqlite extends Pdo
{
	// 构造器
    const BUILDER = builder\Sqlite::class;
    
    /*
     * 获取dsn
     */
    protected function getDsn($config)
    {
        return 'sqlite:'.$config['database'];
    }
    
    /*
     * 获取表字段名
     */
    protected function getFields($table)
    {
        return array_column($this->select("PRAGMA table_info(`$table`)"), 'name');
    }
}