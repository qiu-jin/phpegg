<?php
namespace framework\driver\db;

class Pdo extends Db
{
    protected function connect($config)
    {
		try {
			$dsn = $config['dbtype'] ?? 'mysql';
            $dsn .= ':host='.$config['host'].';dbname='.$config['dbname'];
            if (isset($config['port'])) {
                $dsn .= ';port='.$config['port'];
            }
            if (isset($config['charset'])) {
                $dsn .= ';charset='.$config['charset'];
            }
			$link = new \PDO($dsn, $config['username'], $config['password']);
			$link->exec('SET SQL_MODE=ANSI_QUOTES');
            $this->dbname = $config['dbname'];
            return $link;
		} catch (\PDOException $e) {
			throw new \Exception($e->getMessage());
		}
    }
    
    public function exec($sql, $params = null)
    {
        $this->debug && $this->writeDebug($sql, $params);
        $cmd = trim(strtoupper(strtok($sql, ' ')), "\t(");
        if ($params) {
            $query = $this->prepareExecute($sql, $params);
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
                    throw new \Exception('DB ERROR: ['.$error[1].']'.$error[2]);
                } else {
                    return $query->fetchAll(\PDO::FETCH_ASSOC);
                }
            } else {
                $affected = $this->link->exec($sql);
                if ($affected === false) {
                    $error = $this->link->errorInfo();
                    throw new \Exception('DB ERROR: ['.$error[1].']'.$error[2]);
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
        $this->debug && $this->writeDebug($sql, $params);
        if ($params) {
            return $this->prepareExecute($sql, $params);
        } else {
            $query = $this->link->query($sql);
            if ($query === false) {
                $error = $this->link->errorInfo();
                throw new \Exception('DB ERROR: ['.$error[1].']'.$error[2]);
            }
            return $query;
        }
    }
    
    public function prepareExecute($sql, $params)
    {
        $query = $this->link->prepare($sql);
        if ($query->execute($params)) {
            return $query;
        } else {
            $error = $query->errorInfo();
            if ($error[0] === 'HY093') {
                throw new \Exception('DB ERROR: Invalid parameter number');
            }
            throw new \Exception('DB ERROR: ['.$error[1].']'.$error[2]);
        }
    }
    
    public function fetch($query)
    {
        return $query->fetch(\PDO::FETCH_ASSOC);
    }

    public function fetchRow($query)
    {
        return $query->fetch(\PDO::FETCH_NUM);
    }
    
    public function fetchAll($query)
    {
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function numRows($query)
    {
        return $query->rowCount();
    }
    
    public function affectedRows($query)
    {
        return $query->rowCount();
    }
    
    public function insertId()
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
}