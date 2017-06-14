<?php
namespace framework\driver\nosql;

class Mongo
{
    private $db;
    private $link;
    private $table;
    private $collection;
    private $collections = array();
    
    public function __construct($config)
    {
        try { 
            $this->link = new \mongoClient( $this->config['server'],$this->config);
            $this->db = $this->link->selectDb($this->config['dbname']);
        } catch(\MongoConnectionException $e) {
            throw new \Exception($e->getmessage());
        }
    }
    
    public function table($table)
    {
        try {
            if (!$this->collection && $table !== $this->table) {
                if (!isset($this->collections[$table])) {
                    $this->collections[$table] = $this->db->selectCollection($table);
                }
                $this->table = $table;
                $this->collection = $this->collections[$table];
            }
        } catch(\MongoCursorException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    public function find($where, $fields = null, $limit = 1)
    {
        try {
            
        }
        $this->$collection->select();
    }
    
    public function select($where, $fields = null, $limit = 1)
    {
        try {
            
        }
        $this->$collection->select();
    }
    
    public function insert($data, $replace = false)
    {
        try {
            return $replace ? $this->collection->save($data) : $this->collection->insert($data);
        } catch(\MongoCursorException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    public function insert_all($datas)
    {
        try {
            return $this->collection->batchInsert($datas);
        } catch(\MongoCursorException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    public function update($where, $data)
    {
        try {
            return $this->collection->update($data);
        } catch(\MongoCursorException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    public function delete($where)
    {
        try {
            return $this->collection->remove($datas);
        } catch(\MongoCursorException $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    public function exists()
    {
        
    }
    
    public function count()
    {
        
    }
    
    public function group()
    {
        
    }
    
    public function command()
    {
        
    }
    
    public function execute()
    {
        
    }
    
    private function fields($fields)
    {
        
    }
    
    private function where($where)
    {
        
    }
    
    public function error()
    {
        return $this->link->lastError();
    }
    
    public function __construct()
    {
        $this->link && $this->link->close();
    }
}

