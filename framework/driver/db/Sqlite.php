<?php
namespace framework\driver\db;

class Sqlite extends Pdo
{
    const BUILDER = builder\Sqlite::class;
    
    protected function dsn($config)
    {
        return 'sqlite:'.$config['database'];
    }
    
    protected function getFields($table)
    {
        return array_column($this->exec("PRAGMA table_info(`$table`)"), 'name');
    }
}