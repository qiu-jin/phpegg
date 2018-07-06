<?php
namespace framework\driver\db;

use framework\extend\debug\Db as DBDebug;

abstract class Pdo extends Db
{
    protected $commands;
    
    abstract protected function getDsn($config);
    
    abstract protected function getFields($table);
    
    protected function connect($config)
    {
        $connection = new \PDO($this->getDsn($config), $config['username'], $config['password'], $config['options'] ?? null);
        if (isset($config['attributes'])) {
            foreach ($config['attributes'] as $attribute => $value) {
                $connection->setAttribute($attribute, $value);
            }
        }
        if (isset($this->commands)) {
            foreach ($this->commands as $command) {
                $connection->exec($command);
            }
            $this->commands = null;
        }
        return $connection;
    }
    
    public function exec($sql, $params = null)
    {
        $this->debug && DBDebug::write($sql, $params);
        $cmd = trim(strtoupper(strtok($sql, ' ')), "\t(");
        if ($params) {
            $query = $this->prepareExecute($sql, $params);
            switch ($cmd) {
                case 'SELECT':
                    return $query->fetchAll(\PDO::FETCH_ASSOC);
                case 'INSERT':
                    return $this->connection->lastInsertId();
                case 'UPDATE':
                    return $query->rowCount();
                case 'DELETE':
                    return $query->rowCount();
                default:
                    return true;
            }
        } else {
            if ($cmd === 'SELECT') {
                if ($query = $this->connection->query($sql)) {
                    return $query->fetchAll(\PDO::FETCH_ASSOC);
                }
            } elseif ($cmd === 'INSERT' || $cmd === 'UPDATE' || $cmd === 'DELETE') {
                if ($affected = $this->connection->exec($sql)) {
                    return $cmd === 'INSERT' ? $this->connection->lastInsertId() : $affected;
                }
            } else {
                if (($query = $this->connection->query($sql))) {
                    return true;
                }
            }
            $error = $this->connection->errorInfo();
            throw new \Exception('DB ERROR: ['.$error[1].']'.$error[2]);
        }
    }
    
    public function query($sql, $params = null)
    {
        $this->debug && DBDebug::write($sql, $params);
        if ($params) {
            return $this->prepareExecute($sql, $params);
        } else {
            if ($query = $this->connection->query($sql)) {
                return $query;
            }
            $error = $this->connection->errorInfo();
            throw new \Exception('DB ERROR: ['.$error[1].']'.$error[2]);
        }
    }
    
    public function prepareExecute($sql, $params)
    {
        if ($query = $this->connection->prepare($sql)) {
            if ($query->execute($params)) {
                return $query;
            } else {
                $error = $query->errorInfo();
            }
        } else {
            $error = $this->connection->errorInfo();
        }
        if ($error[0] === 'HY093') {
            throw new \Exception('DB ERROR: Invalid parameter number');
        }
        throw new \Exception('DB ERROR: ['.$error[1].']'.$error[2]);
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
        return $this->connection->lastInsertId();
    }

    public function quote($str)
    {
        return $this->connection->quote($str);
    }
    
    public function begin()
    {
		return $this->connection->beginTransaction();
    }
    
    public function rollback()
    {
        return $this->connection->rollBack();
    }
    
    public function commit()
    {
		return $this->connection->commit();
    }
    
    public function error($query = null)
    {   
        $error = $query ? $query->errorInfo() : $this->connection->errorInfo();
        return array($error[1], $error[2]);
    }
}