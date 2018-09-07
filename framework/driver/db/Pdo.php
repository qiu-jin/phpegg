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
    
    public function select($sql, $params = null)
    {
        $query = $params ? $this->prepareExecute($sql, $params) : $this->connection->query($sql);
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function insert($sql, $params = null, $return_id = false)
    {
        $params ? $this->prepareExecute($sql, $params) : $this->connection->exec($sql);
        if ($return_id) {
            return $this->connection->lastInsertId();
        }
    }
    
    public function update($sql, $params = null)
    {
        if ($params) {
            $this->prepareExecute($sql, $params)->rowCount();
        } else {
            return $this->connection->exec($sql);
        }
    }
    
    public function delete($sql, $params = null)
    {
        return $this->update($sql, $params);
    }
    
    public function exec($sql, $params = null)
    {
        $cmd = trim(strtoupper(strtok($sql, ' ')), "\t(");
        if ($params) {
            $query = $this->prepareExecute($sql, $params);
            switch ($cmd) {
                case 'SELECT':
                    return $query->fetchAll(\PDO::FETCH_ASSOC);
                case 'INSERT':
                    return $this->connection->lastInsertId();
                case 'UPDATE':
                case 'REPLACE':
                case 'DELETE':
                    return $query->rowCount();
                default:
                    return true;
            }
        } else {
            switch ($cmd) {
                case 'SELECT':
                    return $this->realQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);
                case 'INSERT':
                    $this->connection->exec($sql);
                    return $this->connection->lastInsertId();
                case 'UPDATE':
                case 'REPLACE':
                case 'DELETE':
                    return $this->connection->exec($sql);
                default:
                    return (bool) $this->realQuery($sql);
            }
        }
    }
    
    public function query($sql, $params = null)
    {
        return $params ? $this->prepareExecute($sql, $params) : $this->realQuery($sql);
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
    
    public function realQuery($sql)
    {
        $this->debug && DBDebug::write($sql);
        if ($query = $this->connection->query($sql)) {
            return $query;
        }
        throw new \Exception($this->exceptionMessage());
    }
    
    public function realExec($sql)
    {
        $this->debug && DBDebug::write($sql);
        if ($result = $this->connection->exec($sql)) {
            return $result;
        }
        throw new \Exception($this->exceptionMessage());
    }
    
    public function prepareExecute($sql, $params)
    {
        $this->debug && DBDebug::write($sql, $params);
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
        throw new \Exception($this->exceptionMessage($error));
    }
    
    protected function exceptionMessage($error = null)
    {
        $err = $error ?? $this->connection->errorInfo();
        return "DB ERROR: [$err[1]] $err[2]";
    }
}