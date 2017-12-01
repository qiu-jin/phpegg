<?php
namespace framework\driver\db;

use framework\extend\debug\Db as DBDebug;

class Mysqli extends Db
{
    protected function connect($config)
    {
        $link = new \mysqli($config['host'], 
                            $config['username'], 
                            $config['password'], 
                            $config['dbname'], 
                            $config['port'] ?? '3306', 
                            $config['socket'] ?? null
                        );
        if ($link->connect_error) {
            throw new \Exception("MySQL Server Connect Error $link->connect_errno: $link->connect_error");
        }
        if (isset($config['charset'])) {
            $link->set_charset($config['charset']);
        }
        if (isset($config['options'])) {
            foreach ($config['options'] as $option => $value) {
                $link->options($option, $value);
            }
        }
        $link->query("SET sql_mode=''");
        return $link;
    }
    
    public function switch($dbname, callable $call)
    {
        $raw_dbname = $this->dbname;
        try {
            if ($this->link->select_db($dbname)) {
                $this->dbname = $dbname;
                return $call($this);
            }
        } finally {
            $this->dbname = $raw_dbname;
            $this->link->select_db($raw_dbname);
        }
    }
    
    public function async($sql)
    {
        $this->debug && DBDebug::write($sql);
        $query = $this->link->query($sql, MYSQLI_ASYNC);
        if ($query) {
            return $query;
        }
        throw new \Exception('DB ERROR: ['.$this->link->errno.']'.$this->link->error);
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
                    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        } else {
            $query = $this->link->query($sql);
            if (!$query) {
                throw new \Exception('SQL ERROR: ['.$this->link->errno.']'.$this->link->error);
            }
            switch ($cmd) {
                case 'SELECT':
                    return $query->fetch_all(MYSQLI_ASSOC);
                case 'INSERT':
                    return $this->link->insert_id;
                case 'UPDATE':
                    return $this->link->affected_rows;
                case 'DELETE':
                    return $this->link->affected_rows;
                default:
                    return $query->fetch_all(MYSQLI_ASSOC);
            }
        }
        return false;
    }
    
    public function query($sql, array $params = null, $is_assoc = false)
    {
        $this->debug && DBDebug::write($sql, $params, $is_assoc);
        if ($params) {
            return $this->prepareExecute($sql, $params, $is_assoc)->get_result();
        } else {
            $query = $this->link->query($sql);
            if ($query) {
                return $query;
            }
            throw new \Exception('DB ERROR: ['.$this->link->errno.']'.$this->link->error);
        }
    }
    
    public function prepareExecute($sql, $params, $is_assoc)
    {
        $bind_params = [];
        if ($is_assoc) {
            $str = '';
            if (preg_match_all('/\:(\w+)/', $sql, $matchs, PREG_OFFSET_CAPTURE)) {
                $start = 0;
                foreach ($matchs[0] as $i => $match) {
                    $str .= substr($sql, $start, $match[1]-$start).'?';
                    $bind_params[] = &$params[$matchs[1][$i][0]];
                    $start = strlen($match[0]) + $match[1];
                }
                if ($start < strlen($sql)) $str .= substr($sql, $start);
            }
            $sql = $str;
        } else {
            foreach ($params as $k => $v) {
                $bind_params[] = &$params[$k];
            }
        }
        $query = $this->link->prepare($sql);
        if ($query) {
            $type = str_pad('', count($bind_params), 's');
            array_unshift($bind_params, $type);
            $query->bind_param(...$bind_params);
            if (!$query->execute()) {
                throw new \Exception('DB ERROR: ['.$this->link->errno.']'.$this->link->error);
            }
            return $query;
        } else {
            throw new \Exception('DB ERROR: ['.$this->link->errno.']'.$this->link->error);
        }
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
        return $query ? $query->affected_rows : $this->link->affected_rows;
    }
    
    public function insertId()
    {
        return $this->link->insert_id;
    }

    public function quote($str)
    {
        return "'".$this->link->escape_string($str)."'";
    }
    
    public function begin()
    {
		$this->link->autocommit(false);
		return $this->link->begin_transaction();
    }
    
    public function rollback()
    {
		if ($this->link->rollback()) {
			$this->link->autocommit(true);
			return true;
		}
        return false;
    }
    
    public function commit()
    {
		if ($this->link->commit()) {
			$this->link->autocommit(true);
			return true;
		}
		return false;
    }
    
    public function error($query = null)
    {
        $q = $query ?? $this->link;
        return array($q->errno, $q->error)
    }
    
    protected function getFields($table)
    {
        return array_column($this->exec("desc `$table`"), 'Field');
    }

    public function __destruct()
    {
        $this->link->close();
    }
}
