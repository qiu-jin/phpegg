<?php
namespace framework\driver\db;

class Sqlite extends Pdo
{
    const BUILDER = 'framework\driver\db\builder\Sqlite';
    
    protected function dsn($config)
    {
        return 'sqlite:'.$config['database'];
    }
    
    protected function getFields($table)
    {
        /*
        $query = $this->query(".schema `$table`");
        while ($row = $this->fetch($query)) {
            $fields[] = $row['Field'];
        }
        */
    }
}