<?php
namespace framework\driver\db;


class Cluster extends Mysqli
{
    protected $work_link;
    protected $read_link;
    protected $wirte_link;
    
    public function __construct($config)
    {
        $this->config = $config;
        $config['host'] = $config['read'];
        $this->link = $this->connect($config);
        $this->work_link = $this->link;
        $this->read_link = $this->link;
    }
    
    public function link($is_wirte = true)
    {
        if ($is_wirte) {
            if (empty($this->wirte_link)) {
                $config = $this->config;
                $config['host'] = $config['wirte'];
                $this->wirte_link = $this->connect($config);
            }
            return $this->wirte_link;
        } else {
            return $this->read_link;
        }
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
        return $this->link($this->isWirte($sql))->prepare($sql);
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
    
    protected function isWirte(&$sql)
    {
        return trim(strtoupper(strtok($sql, ' ')), "\t(") !== 'SELECT';
    }
    
    protected function callMethod($method, $sql, $params)
    {
        $method = 'parent::'.$method;
        if ($this->isWirte($sql)) {
            $this->link = $this->link();
            $this->work_link = $this->link;
            try {
                $return = $method($sql, $params);
                $this->link = $this->read_link;
                return $return;
            } catch (\Exception $e) {
                $this->link = $this->read_link;
                throw $e;
            }
        } else {
            $this->work_link = $this->link;
            return $method($sql, $params);
        }
    }
}