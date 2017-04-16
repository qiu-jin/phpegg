<?php
namespace framework\driver\db;

use framework\extend\db\Query;
use framework\extend\db\Builder;

abstract class Db
{
    protected $link;
    protected $dbname;
    protected $table_fields;
    
    abstract public function exec($sql);
    
    abstract public function query($sql);
    
    abstract public function fetch($query);
    
    abstract public function fetch_array($query);
    
    abstract public function fetch_all($query);
    
    abstract public function num_rows($query);
    
    abstract public function affected_rows($query);
    
    abstract public function insert_id();
    
    abstract public function begin();
    
    abstract public function rollback();
    
    abstract public function commit();
    
    abstract public function quote($str);
    
    abstract public function error();
    
    abstract public function close();
    
    public function __get($name)
    {
        return new Query($this, $name);
    }
   
    public function table($name)
    {
        return new Query($this, $name);
    }
   
    public function action(callable $call)
    {   
        try {
            $this->begin();
            if ($call($this)) {
                return $this->commit();
            } 
            return $this->rollback();
        } catch (\Exception $e) {
            $this->rollback();
        }
    }

    public function select($table, $fields = '*', $where = [], $option = [])
    {
        $option['where'] = $where;
        $option['fields'] = (array) $fields;
        return $this->exec(...Builder::select($table, $option));
    }

    public function insert($table, $data, $replace = false)
    {
        $sql = ($replace ? 'REPLACE' : 'INSERT')." INTO $table SET ";
        $data = Builder::setData($data);
        return $this->exec($sql.$data[0], $data[1]);
    }
    
    public function update($table, $data, $where, $limit = 1)
    {
        $data = Builder::setData($data);
        $where = Builder::where($where);
        $sql = "UPDATE $table SET ".$data[0].' WHERE '.$where[0];
        return $this->exec($limit > 0 ? "$sql LIMIT $limit" : $sql, array_merge($data[1], $where[1]));
    }
   
    public function delete($table, $where, $limit = 1)
    {
        $where = Builder::where($where);
        $sql = "DELETE FROM `$table` WHERE ".$where[0];
        return $this->exec($limit > 0 ? "$sql LIMIT $limit" : $sql, $where[1]);
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