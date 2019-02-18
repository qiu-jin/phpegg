<?php
namespace framework\driver\db;

class Sqlsrv extends Pdo
{
	// 构造器
    const BUILDER = builder\Sqlsrv::class;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
		parent::__construct($config);
		$this->query('SET QUOTED_IDENTIFIER ON');
		$this->query('SET ANSI_NULLS ON');
    }
	
    /*
     * 获取dsn
     */
    protected function getDsn($config)
    {
        if (strstr(PHP_OS, 'WIN')) {
            $dsn = 'sqlsrv:Database='.$config['dbname'].';Server='.$config['host'];
            if (isset($config['port'])) {
                $dsn .= ','.$config['port'];
            }
        } else {
            $dsn = 'dblib:dbname='.$config['dbname'].';host='.$config['host'];
            if (isset($config['port'])) {
                $dsn .= ':'.$config['port'];
            }
        }
        return $dsn;
    }
    
    /*
     * 获取表字段名
     */
    protected function getFields($table)
    {
        // 待测
        return array_column($this->select("sp_columns [$table]"), 'Field');
    }
}