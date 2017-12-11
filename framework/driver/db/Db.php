<?php
namespace framework\driver\db;

use framework\core\Container;

abstract class Db
{
    protected $sql;
    protected $link;
    protected $debug;
    protected $cache;
    protected $fields;
    protected $dbname;
    protected $cache_config;
    
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
        $this->link = $this->connect($config);
        if (isset($config['cache'])) {
            $this->cache_config = $config['cache'];
        }
        if (isset($config['dbname'])) {
            $this->dbname = $config['dbname'];
        }
        $this->debug = !empty($config['debug']) || APP_DEBUG;
    }
    
    public function __get($name)
    {
        return new query\Query($this, $name);
    }
   
    public function table($name)
    {
        return new query\Query($this, $name);
    }

    public function action(callable $call)
    {
        try {
            $this->begin();
            ($return = $call()) ? $this->commit() : $this->rollback();
            return $return;
        } catch (\Exception $e) {
            $this->rollback();
            throw new \Exception($e->getMessage());
        }
    }
    
    public function fields($table)
    {
        if (isset($this->fields[$table])) {
            return $this->fields[$table];
        } else {
            if (isset($this->cache_config)) {
                if (!isset($this->cache)) {
                    $this->cache = Container::driver('cache', $this->cache_config);
                }
                $key = "_db_$this->dbname$table";
                $fields = $this->cache->get($key);
                if ($fields) {
                    return $fields;
                }
            }
            $fields = $this->getFields($table);
            if (isset($this->cache)) {
                $this->cache->set($key, $fields);
            }
            return $this->fields[$table] = $fields;
        }
    }
    
    public function debug($bool = true)
    {
        $this->debug = (bool) $bool;
    }
    
    public function getConnection()
    {
        return $this->link;
    }
    
    /*
    public function getSql($all = true)
    {
        return $all ? $this->sql : end($this->sql);
    }
    */
}