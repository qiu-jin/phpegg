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
    protected static $write_methods = [
        'insertid', 'affectedrows', 'begin', 'rollback', 'commit', 'transaction', 'switch'
    ];
    
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
    
    public function exec($sql, $params = null)
    {
        return $this->selectDatabase($this->sqlType($sql))->exec($sql, $params);
    }
    
    public function query($sql, $params = null)
    {
        return $this->selectDatabase($this->sqlType($sql))->query($sql, $params);
    }
    
    public function getBuilder()
    {
        return $this->builder;
    }
    
    public function getDatabase($type = null, $sticky = true)
    {
        if ($type == 'write' || $type == 'read') {
            return $this->selectDatabase($type, $sticky);
        }
        return $this->work ?? $this->selectDatabase('read', $sticky);
    }
    
    public function __call($method, $params)
    {
        $m = strtolower($method);
        return $this->getDatabase(in_array($m, self::$write_methods) ? 'write' : null)->$method(...$params);
    }
    
    protected function selectDatabase($type, $sticky = true)
    {
        if (!empty($this->config['sticky']) && $sticky && $this->write) {
            return $this->work = $this->write;
        }
        return $this->work = $this->$type ?? (
            $this->$type = Container::makeDriverInstance('db', ['driver' => $this->config['dbtype']] + $this->config[$type])
        );
    }
    
    protected function sqlType($sql)
    {
        return strtoupper(substr(ltrim($sql), 0, 6)) == 'SELECT' ? 'read' : 'write';
    }
}
