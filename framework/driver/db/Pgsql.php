<?php
namespace framework\driver\db;

class Pgsql extends Pdo
{
    const BUILDER = builder\Pgsql::class;
    
    protected function getDsn($config)
    {
        $dsn = 'pgsql:host='.$config['host'].';dbname='.$config['dbname'];
        if (isset($config['port'])) {
            $dsn .= ';port='.$config['port'];
        }
        return $dsn;
    }
    
    protected function getFields($table)
    {
        return array_column($this->exec("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME = '$table'"), 'column_name');
    }
}