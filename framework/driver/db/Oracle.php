<?php
namespace framework\driver\db;

class Oracle extends Pdo
{
    const BUILDER = 'framework\driver\db\builder\Oracle';
    
    protected function dsn($config)
    {
        $dsn = 'pgsql:dbname='.$config['dbname'].';host='.$config['host'];
        $dsn .= isset($config['port']) ? ':'.$config['port'] : '/';
        return $dsn;
    }
    
    protected function getFields($table)
    {
        /*
        $query = $this->query("describe `$table`");
        while ($row = $this->fetch($query)) {
            $fields[] = $row['Field'];
        }
        */
    }
}