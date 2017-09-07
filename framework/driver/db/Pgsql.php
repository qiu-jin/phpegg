<?php
namespace framework\driver\db;

class Pgsql extends Pdo
{
    const BUILDER = 'framework\driver\db\builder\Pgsql';
    
    protected function dsn($config)
    {
        $dsn = 'pgsql:host='.$config['host'].';dbname='.$config['dbname'];
        if (isset($config['port'])) {
            $dsn .= ';port='.$config['port'];
        }
        return $dsn;
    }
    
    protected function getFields($table)
    {
        /*
        $query = $this->query("\d `$table`");
        while ($row = $this->fetch($query)) {
            $fields[] = $row['Field'];
        }
        */
    }
}