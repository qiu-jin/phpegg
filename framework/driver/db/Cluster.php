<?php
namespace framework\driver\db;


class Cluster extends Mysqli
{
    protected $work_connection;
    protected $read_connection;
    protected $wirte_connection;
    
    public function __construct($config)
    {
        $this->config = $config;
        $config['host'] = $config['read'];
        $this->connection = $this->connect($config);
        $this->work_connection = $this->connection;
        $this->read_connection = $this->connection;
    }
    
    public function exec($sql, $params = null)
    {
        return $this->callMethod('exec', $sql, $params);
    }
    
    public function query($sql, $params = null)
    {
        return $this->callMethod('query', $sql, $params);
    }
    
    public function prepare($sql)
    {
        return $this->getConnection($this->isWirte($sql))->prepare($sql);
    }
    
    public function insertId()
    {
        return $this->getConnection()->lastInsertId();
    }
    
    public function begin()
    {
		return $this->getConnection()->beginTransaction();
    }
    
    public function rollback()
    {
        return $this->getConnection()->rollBack();
    }
    
    public function commit()
    {
		return $this->getConnection()->commit();
    }
    
    public function error($query = null)
    {
        return parent::error($query ? $query : $this->work_connection);
    }
    
    public function getConnection($is_wirte = true)
    {
        if ($is_wirte) {
            if (isset($this->wirte_connection)) {
                return $this->wirte_connection;
            }
            $config = $this->config;
            $config['host'] = $config['wirte'];
            return $this->wirte_connection = $this->connect($config);
        } else {
            return $this->read_connection;
        }
    }
    
    protected function isWirte(&$sql)
    {
        return trim(strtoupper(strtok($sql, ' ')), "\t(") !== 'SELECT';
    }
    
    protected function callMethod($method, $sql, $params)
    {
        if ($this->isWirte($sql)) {
            $this->connection = $this->getConnection();
            $this->work_connection = $this->connection;
            try {
                $return = parent::{$method}($sql, $params);
                $this->connection = $this->read_connection;
                return $return;
            } catch (\Exception $e) {
                $this->connection = $this->read_connection;
                throw $e;
            }
        } else {
            $this->work_connection = $this->connection;
            return $parent::{$method}($sql, $params);
        }
    }
    
    public function __destruct()
    {
        $this->connection       = null;
        $this->work_connection  = null;
        $this->read_connection  = null;
        $this->wirte_connection = null;
    }
}