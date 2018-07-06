<?php
namespace framework\driver\db;

use framework\core\Container;

abstract class Db
{
    protected $sql;
    protected $debug;
    protected $dbname;
    protected $connection;
    protected $fields;
    protected $fields_cache;
    protected $fields_cache_config;
    
    const BUILDER = builder\Builder::class;
    
    abstract public function exec($sql);
    
    abstract public function query($sql);
    
    abstract public function fetch($query);
    
    abstract public function fetchRow($query);
    
    abstract public function fetchAll($query);
    
    abstract public function numRows($query);
    
    abstract public function affectedRows($query);
    
    abstract public function insertId();
    
    abstract public function begin();
    
    abstract public function rollback();
    
    abstract public function commit();
    
    abstract public function quote($str);
    
    abstract public function error();
    
    public function __construct($config)
    {
        $this->connection = $this->connect($config);
        $this->dbname = $config['dbname'];
        $this->debug  = !empty($config['debug']) || APP_DEBUG;
        if (isset($config['fields_cache_config'])) {
            $this->fields_cache_config = $config['fields_cache_config'];
        }
    }
    
    public function __get($name)
    {
        return $this->table($name);
    }
   
    public function table($name)
    {
        return new query\Query($this, $name);
    }

    public function transaction(callable $call)
    {
        try {
            $this->begin();
            ($return = $call()) ? $this->commit() : $this->rollback();
            return $return;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    public function fields($table)
    {
        if (isset($this->fields[$this->dbname][$table])) {
            return $this->fields[$this->dbname][$table];
        } else {
            if (isset($this->fields_cache_config)) {
                if (!isset($this->fields_cache)) {
                    $this->fields_cache = Container::driver('cache', $this->fields_cache_config);
                }
                if ($fields = $this->fields_cache->get($key = "$this->dbname-$table")) {
                    return $fields;
                }
            }
            $fields = $this->getFields($table);
            if (isset($this->fields_cache)) {
                $this->fields_cache->set($key, $fields);
            }
            return $this->fields[$this->dbname][$table] = $fields;
        }
    }
    
    public function debug($bool = true)
    {
        $this->debug = (bool) $bool;
    }
    
    public function getBuilder()
    {
        return static::BUILDER;
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    /*
    public function getSql($all = true)
    {
        return $all ? $this->sql : end($this->sql);
    }
    */
}