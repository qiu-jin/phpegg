<?php
namespace framework\driver\cache;

/*
 * key char(128) NOT NULL PRIMARY KEY
 * value BLOB
 * expiration int(11)
 */

class Db extends Cache
{
    protected $table;

    protected function init($config)
    {
        $this->link = db($config['db']);
        $this->table = $config['table'];
    }
    
    public function get($key, $default = null)
    {
        $cache = $this->link->exec("SELECT `value`, `expiration` FROM `$this->table` WHERE `key` = ?", [$key]);
        if ($cache) {
            if ($cache[0]['expiration'] > time()) {
                return $this->unserialize($cache[0]['value']);
            }
            $this->link->exec("DELETE FROM `$this->table` WHERE `key` = ?", [$key]);
        }
        return $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        return $this->link->exec("REPLACE INTO `$this->table` SET `key` = ?, `value` = ?, `expiration` = ?", [
            $key,
            $this->serialize($value),
            $ttl ? time() + $ttl : time() + 311040000
        ]);
    }

    public function has($key)
    {
        $cache = $this->link->exec("SELECT `expiration` FROM `$this->table` WHERE `key` = ?", [$key]);
        if ($cache) {
            if ($cache[0]['expiration'] > time()) {
                return true;
            }
            $this->link->exec("DELETE FROM `$this->table` WHERE `key` = ?", [$key]);
        }
        return false;
    }
    
    public function delete($key)
    {
        return (bool) $this->link->exec("DELETE FROM `$this->table` WHERE `key` = ?", [$key]);
    }
    
    public function getMultiple(array $keys, $default = null)
    {

    }
    
    public function setMultiple(array $values, $ttl = null)
    {

    }
    
    public function deleteMultiple(array $keys)
    {

    }
    
    public function clear()
    {
        return (bool) $this->link->query("TRUNCATE `$this->table`");
    }
    
    public function gc()
    {
        $this->link->exec("DELETE FROM `$this->table` WHERE `expiration` < ", [time()]);
    }
}
