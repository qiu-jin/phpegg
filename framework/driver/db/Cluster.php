<?php
namespace framework\driver\db;

use framework\core\Container;

class Cluster
{
    protected $work;
    protected $read;
    protected $write;
    protected $config;
    protected $builder;
    
    protected static $write_methods = ['insertId', 'affectedRows', 'begin', 'rollback', 'commit', 'transaction'];
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->builder = (__NAMESPACE__.'\\'.ucfirst($config['dbtype']))::BUILDER;
    }
    
    public function __get($name)
    {
        return $this->table($name);
    }

    public function table($name)
    {
        return new query\Query($this, $name);
    }
    
    public function __call($method, $params)
    {
        if (in_array($method, self::$write_methods)) {
            return $this->getDatabase('write')->$method(...$params);
        } else {
            return ($this->work ?? $this->getDatabase('read'))->$method(...$params);
        }
    }
    
    public function exec($sql, $params = null)
    {
        return $this->getDatabase($this->sqlType($sql))->exec($sql, $params);
    }
    
    public function query($sql, $params = null)
    {
        return $this->getDatabase($this->sqlType($sql))->query($sql, $params);
    }
    
    public function getBuilder()
    {
        return $this->builder;
    }
    
    public function selectDatabase($type)
    {
        return $this->work = $this->$type ?? (
            $this->$type = Container::makeDriverInstance('db', ['driver' => $this->config['dbtype']] + $this->config[$type])
        );
    }
    
    protected function getDatabase($type)
    {
        if (!empty($this->config['sticky']) && isset($this->write)) {
            $type = 'write';
        }
        return $this->selectDatabase($type);
    }
    
    protected function sqlType($sql)
    {
        return strtoupper(substr(ltrim($sql), 0, 6)) == 'SELECT' ? 'read' : 'write';
    }
}