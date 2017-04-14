<?php
namespace Framework\Driver\Db;

class Mysqli extends Db
{
    public function __construct($config)
    {
        $this->config = $config;
        $this->link = $this->connect();
    }
    
    protected function connect()
    {
        $link = new \mysqli($this->config['host'], $this->config['user'], $this->config['passwd'], $this->config['name']);
        if ($link->connect_error) {
            throw new \Exception('MySQL Server Connect Error :['.$this->link->connect_errno.']'.$this->link->connect_error);
        }
        if ($charset) {
            $link->set_charset($charset);
        }
        $link->query("SET sql_mode=''");
        $this->dbname = $this->config['name'];
        return $link;
    }
    
    public function switch($dbname, callable $call)
    {
        $raw_dbname = $this->dbname;
        $this->dbname = $dbname;
        $this->link->select_db($dbname);
        try {
            $call($this);
        } catch (\Exception $e) {
            
        }
        $this->dbname = $raw_dbname;
        $this->link->select_db($raw_dbname);
    }
    
    public function async($sql, callable $call = null)
    {
        $query = $this->link->query($sql, MYSQLI_ASYNC);
    }
    
    public function exec($sql, $params = null)
    {
        $cmd = trim(strtoupper(strtok($sql, ' ')),"\t(");
        if ($params) {
            $query = $this->prepare_execute($sql, $params);
            switch ($cmd) {
                case 'INSERT':
                    return $query->insert_id;
                case 'UPDATE':
                    return $query->affected_rows;
                case 'DELETE':
                    return $query->affected_rows;
                case 'SELECT':
                    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
                default:
                    return true;
            }
        } else {
            $query = $this->link->query($sql);
            if (!$query) {
                throw new \Exception('SQL ERROR: ['.$this->link->errno.']'.$this->link->error);
            }
            switch ($cmd) {
                case 'INSERT':
                    return $this->link->insert_id;
                case 'UPDATE':
                    return $this->link->affected_rows;
                case 'DELETE':
                    return $this->link->affected_rows;
                case 'SELECT':
                    return $query->fetch_all(MYSQLI_ASSOC);
                default:
                    return true;
            }
        }
        return false;
    }
    
    public function query($sql, $params = null)
    {
        if ($params) {
            return $this->prepare_execute($sql, $params)->get_result();
        } else {
            $query = $this->link->query($sql);
            if (!$query) {
                throw new \Exception('SQL ERROR: ['.$this->link->errno.']'.$this->link->error);
            }
            return $query;
        }
    }
    
    public function prepare_execute($sql, $params)
    {
        $new_params = array(0 => '');
        if (isset($params[0])) {
            foreach ($params as $k => $v) {
                $new_params[0] .= 's'; 
                $new_params[] = &$params[$k];
            }
            $query = $this->link->prepare($sql);
        } else {
            $new_sql = '';
            if (preg_match_all('/\:(\w+)/', $sql, $matchs, PREG_OFFSET_CAPTURE)) {
                $start = 0;
                foreach ($matchs[0] as $i => $match) {
                    $new_sql .= substr($sql, $start, $match[1]-$start).'?';
                    $new_params[0] .= 's';
                    $new_params[] = &$params[$matchs[1][$i][0]];
                    $start = strlen($match[0]) + $match[1];
                }
                if ($start < strlen($sql)) $new_sql .= substr($sql, $start);
            }
            $query = $this->link->prepare($new_sql);
        }
        if ($query) {
            $query->bind_param(...$new_params);
            $query->execute();
            return $query;
        } else {
            throw new \Exception('SQL ERROR: ['.$this->link->errno.']'.$this->link->error);
        }
    }
    
    public function prepare($sql)
    {
        return $this->link->prepare($sql);
    }
    
    public function execute($query, $params)
    {
        return $query->execute($params);
    }
    
    public function fetch($query, $type = 'ASSOC')
    {
        switch ($type) {
            case 'ASSOC':
                return $query->fetch_assoc();
            case 'NUM':
                return $query->fetch_row();
            case 'OBJECT':
                return $query->fetch_object();
            default:
                return $query->fetch_array();
        }
    }
    
    public function fetch_all($query)
    {
        return $query->fetch_all(MYSQLI_ASSOC);
    }
    
    public function fetch_array($query)
    {
        return $query->fetch_assoc();
    }
    
    public function fetch_row($query)
    {
        return $query->fetch_row();
    }
    
    public function num_rows($query)
    {
        return $query->num_rows;
    }
    
    public function affected_rows($query = null)
    {
        return $query ? $query->affected_rows : $this->link->affected_rows;
    }
    
    public function insert_id()
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
        return $query ? array($query->errno, $query->error) : array($this->link->errno, $this->link->error);
    }

    public function close()
    {
        return $this->link->close();
    }
    
    public function __destruct()
    {
        $this->close();
    }
}
