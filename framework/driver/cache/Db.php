<?php
namespace framework\driver\cache;

/*
    key 
    value
    expired
*/
class Db extends Cache
{
    private $db;
    private $table;

    protected function init($config)
    {
        $this->db = db($config['db']);
        $this->table = $config['table'];
    }
    
    public function get($key, $default = null)
    {
        $value = $this->db->exec("SELECT value FROM $this->table WHERE key = ? AND expired > ?", [$key, time()]);
        return $value ? $this->unserialize($value) : $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        $expired = $ttl ? time()+$ttl : time()+311040000;
        return $this->db->exec("REPLACE INTO $this->table SET key = ?, value = ?, expired = ?", [$key, $this->serialize($value), $expired]);
    }

    public function has($key)
    {
        $query = $this->db->query("SELECT EXISTS(SELECT * FROM $this->table WHERE key = ? AND expired > ?)", [$key, time()]);
        return $query && !empty($this->db->fetchRow($query)[0]);
    }
    
    public function delete($key)
    {
        return $this->db->exec("DELETE FROM $this->table WHERE key = ?", [$key]);
    }
    
    public function clear()
    {
        $this->db->query("RUNCATE $this->table");
    }
    
    public function gc()
    {
        $this->db->exec("DELETE FROM $this->table WHERE expired < ", [time()]);
    }
}
