<?php
namespace framework\driver\db;

class Mysql extends Pdo
{   
    protected function dsn($config)
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
        $this->commands[] = 'SET SQL_MODE=ANSI_QUOTES';
        return $dsn;
    }
    
    protected function getFields($table)
    {
        $query = $this->query("desc $table");
        while ($row = $this->fetch($query)) {
            $fields[] = $row['Field'];
        }
    }
}
