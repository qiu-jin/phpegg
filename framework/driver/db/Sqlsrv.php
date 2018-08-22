<?php
namespace framework\driver\db;

class Sqlsrv extends Pdo
{
    const BUILDER = builder\Sqlsrv::class;
    
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
        $this->commands[] = 'SET QUOTED_IDENTIFIER ON';
        $this->commands[] = 'SET ANSI_NULLS ON';
        return $dsn;
    }
    
    protected function getFields($table)
    {
        // 待测
        return array_column($this->select("sp_columns [$table]"), 'Field');
    }
}