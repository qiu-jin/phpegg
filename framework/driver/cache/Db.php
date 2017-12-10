<?php
namespace framework\driver\cache;

use framework\core\Container;

/*
 * key char(128) NOT NULL PRIMARY KEY
 * value BLOB
 * expiration int(11)
 */
class Db extends Cache
{
    protected $db;
    protected $table;
    protected $gc_maxlife;

    protected function init($config)
    {
        $this->db = Container::driver('db', $config['db']);
        $this->table = $config['table'] ?? 'cache';
        $this->gc_maxlife = $config['gc_maxlife'] ?? 2592000;
    }
    
    public function get($key, $default = null)
    {
        $cache = $this->db->exec("SELECT value FROM $this->table WHERE key = ? AND 'expiration' > ?", [
            $key, time()
        ]);
        return $cache ? $this->unserialize($cache[0]['value']) : $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        return (bool) $this->db->exec("REPLACE INTO $this->table SET key = ?, value = ?, expiration = ?", [
            $key,
            $this->serialize($value),
            time() + ($ttl ?? $this->gc_maxlife)
        ]);
    }

    public function has($key)
    {
        return (bool) $this->db->exec("SELECT value FROM $this->table WHERE key = ? AND 'expiration' > ?", [
            $key, time()
        ]);
    }
    
    public function delete($key)
    {
        return (bool) $this->db->exec("DELETE FROM $this->table WHERE key = ?", [$key]);
    }
    
    public function getMultiple(array $keys, $default = null)
    {
        $in = implode(",", array_fill(0, count($keys), '?'));
        $data = $this->db->exec("SELECT key, value FROM $this->table WHERE key IN ($in) AND 'expiration' > ".time(), $keys);
        $caches = array_column($data, 'value', 'key');
        foreach ($keys as $key) {
            $caches[$key] = isset($caches[$key]) ? $this->unserialize($caches[$key]) : $default;
        }
        return $caches;
    }
    
    public function deleteMultiple(array $keys)
    {
        $in = implode(",", array_fill(0, count($keys), '?'));
        return (bool) $this->db->exec("DELETE FROM $this->table WHERE key IN ($in)", [$keys]);
    }
    
    public function increment($key, $value = 1)
    {
        return (bool) $this->db->exec("UPDATE $this->table SET value = value + ? WHERE key = ?", [$value, $key]);
    }
    
    public function decrement($key, $value = 1)
    {
        return (bool) $this->db->exec("UPDATE $this->table SET value = value - ? WHERE key = ?", [$value, $key]);
    }
    
    public function clear()
    {
        return (bool) $this->db->query("TRUNCATE $this->table");
    }
    
    public function gc()
    {
        $this->db->exec("DELETE FROM $this->table WHERE expiration < ", [time()]);
    }
}
