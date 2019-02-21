<?php
namespace framework\driver\cache;

/*
 * http://php.net/memcached
 */
class Memcached extends Cache
{
	// 连接实例
    protected $connection;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $connection = new \Memcached;
        if (isset($config['options'])) {
            $connection->setOptions($config['options']);
        }
        if (isset($config['timeout'])) {
            $connection->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $config['timeout']);
        }
        $hosts = is_array($config['hosts']) ? $config['hosts'] : explode(',', $config['hosts']);
        $port  = $config['port'] ?? 11211;
        foreach ($hosts as $i => $host) {
            $connection->addServer($host, $port, 1);
        }
        if (isset($config['username']) && isset($config['password'])) {
            $connection->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $connection->setSaslAuthData($config['username'], $config['password']);
        }
        $this->connection = $connection;
    }
    
    /*
     * 获取
     */
    public function get($key, $default = null)
    {
        return $this->connection->get($key) ?? $default;
    }
	
    /*
     * 检查
     */
    public function has($key)
    {
        return (bool) $this->connection->get($key);
    }
    
    /*
     * 设置
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->connection->set($key, $value, $ttl ?? 0);
    }

    /*
     * 删除
     */
    public function delete($key)
    {
        return $this->connection->delete($key);
    }
	
    /*
     * 自增
     */
    public function increment($key, $value = 1)
    {
        return $this->connection->increment($key, $value);
    }
    
    /*
     * 自减
     */
    public function decrement($key, $value = 1)
    {
        return $this->connection->decrement($key, $value);
    }
    
    /*
     * 获取多个
     */
    public function getMultiple(array $keys, $default = null)
    {
        if (($caches = $this->connection->getMulti($keys)) && $default !== null) {
            foreach ($keys as $k) {
                if (!isset($caches[$k])) {
                    $caches[$k] = $default;
                }
            }
        }
        return $caches;
    }
    
    /*
     * 设置多个
     */
    public function setMultiple(array $values, $ttl = null)
    {
        return $this->connection->setMulti($values, $ttl ?? 0);
    }
    
    /*
     * 删除多个
     */
    public function deleteMultiple(array $keys)
    {
        return $this->connection->deleteMulti($keys);
    }
	
    /*
     * 清理
     */
    public function clean()
    {
        return $this->connection->flush();
    }
    
    /*
     * 获取连接
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /*
     * 析构函数
     */
    public function __destruct()
    {
        $this->connection->quit();
    }
}
