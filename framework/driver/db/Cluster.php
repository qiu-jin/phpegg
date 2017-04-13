<?php
namespace Framework\Driver\Db;

class Cluster extends Pdo
{
    protected $work_link;
    protected $read_link;
    protected $wirte_link;
    
    public function __construct($config)
    {
        $this->$config = $config;
        $this->link = $this->connect($this->config['read'], $this->config['user'], $this->config['passwd'], $this->config['name'], $this->config['charset']);
        $this->cur_link = $this->link;
        $this->read_link = $this->link;
    }
    
    public function link($is_wirte = true)
    {
        if ($is_wirte) {
            if (empty($this->wirte_link)) {
                $this->wirte_link = $this->connect($this->config['wirte'], $this->config['user'], $this->config['passwd'], $this->config['name'], $this->config['charset']);
            }
            return $this->wirte_link;
        } else {
            return $this->read_link;
        }
    }
    
    public function exec($sql, $params = null)
    {
        return $this->sql_method('exec', $sql, $params);
    }
    
    public function query($sql, $params = null)
    {
        return $this->sql_method('query', $sql, $params);
    }
    
    public function prepare($sql)
    {
        return $this->link($this->is_wirte($sql))->prepare($sql);
    }
    
    public function insert_id()
    {
        return $this->link()->lastInsertId();
    }
    
    public function begin()
    {
		return $this->link()->beginTransaction();
    }
    
    public function rollback()
    {
        return $this->link()->rollBack();
    }
    
    public function commit()
    {
		return $this->link()->commit();
    }
    
    public function error($query = null)
    {
        return parent::error($query ? $query : $this->work_link);
    }
    
    public function close()
    {
        $this->link  = null;
        $this->work_link  = null;
        $this->read_link  = null;
        $this->wirte_link = null;
    }
    
    private function is_wirte(&$sql)
    {
        return trim(strtoupper(strtok($sql, ' '))) !== 'INSERT';
    }
    
    private function sql_method($method, $sql, $params)
    {
        $method = 'parent::'.$method;
        if ($this->is_wirte($sql)) {
            $this->link = $this->link();
            $this->work_link = $this->link;
            try {
                $return = $method($sql, $params);
                $this->link = $this->read_link;
                return $return;
            } catch (\Exception $e) {
                $this->link = $this->read_link;
                throw new Exception($e->getMessage());
            }
        } else {
            $this->work_link = $this->link;
            return $method($sql, $params);
        }
    }
}