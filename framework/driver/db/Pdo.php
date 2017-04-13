<?php
namespace Framework\Driver\Db;

class Pdo extends Db
{
    public function __construct($config)
    {
        $this->connect($config);
    }
    
    protected function connect($config)
    {
		try {
            $this->config = $config;
			$commands = array();
			$dsn = '';
			$type = isset($config['dbtype']) ? $config['dbtype'] : 'mysql';
			switch ($type) {
				case 'mysql':
                    $dsn = $type.':host='.$config['host'].(!empty($config['port']) ? ';port='.$config['port'] : '').';dbname='.$config['dbname'];
                    if (isset($config['charset'])) $dsn .= ';charset='.$config['charset'];
					$commands[] = 'SET SQL_MODE=ANSI_QUOTES';
					break;
                default:
                    throw new Exception('未知数据库类型');
			}
            $this->dbname = $config['dbname'];
			$this->link = new \PDO($dsn, $config['username'], $config['password']);
			foreach ($commands as $value) {
				$this->link->exec($value);
			}
		} catch (PDOException $e) {
			throw new Exception($e->getMessage());
		}
    }
    
    public function exec($sql, $params = null)
    {
        $cmd = trim(strtoupper(strtok($sql, ' ')), "\t(");
        if ($params) {
            $query = $this->prepare_execute($sql, $params);
            if ($query) {
                switch ($cmd) {
                    case 'INSERT':
                        return $this->link->lastInsertId();
                    case 'UPDATE':
                        return $query->rowCount();
                    case 'DELETE':
                        return $query->rowCount();
                    case 'SELECT':
                        return $query->fetchAll(\PDO::FETCH_ASSOC);
                    default:
                        return true;
                }
            }
        } else {
            if ($cmd === 'SELECT') {
                $query = $this->link->query($sql);
                if ($query === false) {
                    $error = $this->link->errorInfo();
                    throw new \Exception('SQL ERROR: ['.$error[1].']'.$error[2]);
                } else {
                    return $query->fetchAll(\PDO::FETCH_ASSOC);
                }
            } else {
                $affected = $this->link->exec($sql);
                if ($affected === false) {
                    $error = $this->link->errorInfo();
                    throw new \Exception('SQL ERROR: ['.$error[1].']'.$error[2]);
                } else {
                    if ($cmd === 'INSERT') {
                        return $this->link->lastInsertId();
                    }
                    return $affected;
                }
            }
        }
        return false;
    }
    
    public function query($sql, $params = null)
    {
        if ($params) {
            return $this->prepare_execute($sql, $params);
        } else {
            $query = $this->link->query($sql);
            if ($query === false) {
                $error = $this->link->errorInfo();
                throw new \Exception('SQL ERROR: ['.$error[1].']'.$error[2]);
            }
            return $query;
        }
    }
    
    public function prepare_execute($sql, $params)
    {
        $query = $this->link->prepare($sql);
        if (@$query->execute($params)) {
            return $query;
        } else {
            $error = $query->errorInfo();
            if ($error[0] === 'HY093') {
                throw new \Exception('SQL ERROR: Invalid parameter number');
            }
            throw new \Exception('SQL ERROR: ['.$error[1].']'.$error[2]);
        }
    }
    
    public function prepare($sql)
    {
        return $this->link->prepare($sql);
    }
    
    public function execute($prepare, $params)
    {
        return $prepare->execute($params);
    }
    
    public function fetch($query, $type = 'ASSOC')
    {
        switch ($type) {
            case 'ASSOC':
                return $query->fetch(\PDO::FETCH_ASSOC);
            case 'NUM':
                return $query->fetch(\PDO::FETCH_NUM);
            case 'OBJECT':
                return $query->fetch(\PDO::FETCH_OBJ);
            default:
                return $query->fetch(\PDO::FETCH_BOTH);
        }
    }
    
    public function fetch_all($query)
    {
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetch_array($query)
    {
        return $query->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function fetch_row($query)
    {
        return $query->fetch(\PDO::FETCH_NUM);
    }
    
    public function num_rows($query)
    {
        return $query->rowCount();
    }
    
    public function affected_rows($query)
    {
        return $query->rowCount();
    }
    
    public function insert_id()
    {
        return $this->link->lastInsertId();
    }

    public function quote($str)
    {
        return $this->link->quote($str);
    }
    
    public function begin()
    {
		return $this->link->beginTransaction();
    }
    
    public function rollback()
    {
        return $this->link->rollBack();
    }
    
    public function commit()
    {
		return $this->link->commit();
    }
    
    public function error($query = null)
    {   
        $error = $query ? $query->errorInfo : $this->link->errorInfo;
        return array($error[1], $error[2]);
    }
    
    public function close()
    {
        return $this->link = null;
    }
    /*
    public function __destruct()
    {
        var_dump($this->config);
    }
    */
}