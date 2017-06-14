<?php
namespace framework\driver\db;

use framework\core\Logger;

abstract class Db
{
    protected $link;
    protected $dbname;
    protected $builder;
    protected $table_fields;
    
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
        $this->builder = new query\builder\Prepare();
        if (APP_DEBUG) {
            $this->debug = true;
        }
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
            return $call($this) ? $this->commit() : $this->rollback();
        } catch (\Exception $e) {
            $this->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function select($table, $fields = '*', $where = [], $option = [])
    {
        $option['where'] = $where;
        $option['fields'] = (array) $fields;
        return $this->exec(...$this->builder->select($table, $option));
    }

    public function insert($table, $data, $replace = false)
    {
        $sql = ($replace ? 'REPLACE' : 'INSERT')." INTO $table SET ";
        $data = $this->builder->setData($data);
        return $this->exec($sql.$data[0], $data[1]);
    }
    
    public function update($table, $data, $where, $limit = 0)
    {
        list($set, $params) = $this->builder->setData($data);
        $sql = "UPDATE $table SET ".$set.' WHERE '.$this->builder->whereClause($where, $params);
        return $this->exec($limit > 0 ? "$sql LIMIT $limit" : $sql, $params);
    }
   
    public function delete($table, $where, $limit = 0)
    {
        $params = [];
        $sql = "DELETE FROM `$table` WHERE ".$this->builder->whereClause($where, $params);
        return $this->exec($limit > 0 ? "$sql LIMIT $limit" : $sql, $params);
    }
    
    public function builder()
    {
        return $this->builder;
    }
    
    public function debug()
    {
        $this->debug = true;
    }
    
    protected function setLog($sql, $params)
    {
        logger::write(Logger::DEBUG, $this->builder->implodeParams($sql, $params));
    }
    
    public function getFields($table)
    {
        if (isset($this->table_fields[$table])) {
            return $this->table_fields[$table];
        } else {
            $query = $this->query("desc $table");
            while ($row = $this->fetch($query)) {
                $fields[] = $row['Field'];
            }
            return $this->table_fields[$table] = $fields;
        }
    }
}