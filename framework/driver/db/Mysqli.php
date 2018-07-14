<?php
namespace framework\driver\db;

use framework\extend\debug\Db as DBDebug;

class Mysqli extends Db
{
    protected function connect($config)
    {
        $connection = new \mysqli(
            $config['host'], 
            $config['username'], 
            $config['password'], 
            $config['dbname'], 
            $config['port'] ?? '3306', 
            $config['socket'] ?? null
        );
        if ($connection->connect_error) {
            throw new \Exception("Server Connect Error [$connection->connect_errno] $connection->connect_error");
        }
        if (isset($config['charset'])) {
            $connection->set_charset($config['charset']);
        }
        if (isset($config['options'])) {
            foreach ($config['options'] as $option => $value) {
                $connection->options($option, $value);
            }
        }
        return $connection;
    }
    
    public function switch($dbname, callable $call)
    {
        $raw_dbname = $this->dbname;
        try {
            if ($this->connection->select_db($dbname)) {
                $this->dbname = $dbname;
                return $call($this);
            }
        } finally {
            $this->dbname = $raw_dbname;
            $this->connection->select_db($raw_dbname);
        }
    }
    
    public function exec($sql, array $params = null, $is_assoc = false)
    {
        $this->debug && DBDebug::write($sql, $params, $is_assoc);
        $cmd = trim(strtoupper(strtok($sql, ' ')),"\t(");
        if ($params) {
            $query = $this->prepareExecute($sql, $params, $is_assoc);
            switch ($cmd) {
                case 'SELECT':
                    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
                case 'INSERT':
                    return $query->insert_id;
                case 'UPDATE':
                    return $query->affected_rows;
                case 'DELETE':
                    return $query->affected_rows;
                default:
                    return true;
            }
        } else {
            if (!$query = $this->connection->query($sql)) {
                throw new \Exception($this->exceptionMessage());
            }
            switch ($cmd) {
                case 'SELECT':
                    return $query->fetch_all(MYSQLI_ASSOC);
                case 'INSERT':
                    return $this->connection->insert_id;
                case 'UPDATE':
                    return $this->connection->affected_rows;
                case 'DELETE':
                    return $this->connection->affected_rows;
                default:
                    return (bool) $query;
            }
        }
    }
    
    public function query($sql, array $params = null, $is_assoc = false)
    {
        $this->debug && DBDebug::write($sql, $params, $is_assoc);
        if ($params) {
            return $this->prepareExecute($sql, $params, $is_assoc)->get_result();
        } else {
            if ($query = $this->connection->query($sql)) {
                return $query;
            }
            throw new \Exception($this->exceptionMessage());
        }
    }
    
    public function prepareExecute($sql, $params, $is_assoc)
    {
        $bind_params = [];
        if ($is_assoc) {
            if (preg_match_all('/\:(\w+)/', $sql, $matchs, PREG_OFFSET_CAPTURE)) {
                $str = '';
                $start = 0;
                foreach ($matchs[0] as $i => $match) {
                    $str .= substr($sql, $start, $match[1]-$start).'?';
                    $bind_params[] = &$params[$matchs[1][$i][0]];
                    $start = strlen($match[0]) + $match[1];
                }
                if ($start < strlen($sql)) {
                    $str .= substr($sql, $start);
                }
                $sql = $str;
            }
        } else {
            foreach ($params as $k => $v) {
                $bind_params[] = &$params[$k];
            }
        }
        if ($query = $this->connection->prepare($sql)) {
            $type  = str_pad('', count($bind_params), 's');
            array_unshift($bind_params, $type);
            $query->bind_param(...$bind_params);
            if ($query->execute()) {
                return $query;
            }
        }
        throw new \Exception($this->exceptionMessage());
    }
    
    public function fetch($query)
    {
        return $query->fetch_assoc();
    }
    
    public function fetchRow($query)
    {
        return $query->fetch_row();
    }
    
    public function fetchAll($query)
    {
        return $query->fetch_all(MYSQLI_ASSOC);
    }
    
    public function numRows($query)
    {
        return $query->num_rows;
    }
    
    public function affectedRows($query = null)
    {
        return $query ? $query->affected_rows : $this->connection->affected_rows;
    }
    
    public function insertId()
    {
        return $this->connection->insert_id;
    }

    public function quote($str)
    {
        return "'".$this->connection->escape_string($str)."'";
    }
    
    public function begin()
    {
		return $this->connection->autocommit(false) && $this->connection->begin_transaction();
    }
    
    public function rollback()
    {
        return $this->connection->rollback() && $this->connection->autocommit(true);
    }
    
    public function commit()
    {
		return $this->connection->commit() && $this->connection->autocommit(true);
    }
    
    public function error($query = null)
    {
        $q = $query ?? $this->connection;
        return array($q->errno, $q->error);
    }
    
    protected function getFields($table)
    {
        return array_column($this->exec("desc `$table`"), 'Field');
    }
    
    protected function exceptionMessage()
    {
        return 'DB ERROR: ['.$this->connection->errno.'] '.$this->connection->error;
    }

    public function __destruct()
    {
        $this->connection->close();
    }
}
