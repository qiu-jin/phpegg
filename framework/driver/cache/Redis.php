<?php
namespace framework\driver\cache;

/*
 * https://github.com/phpredis/phpredis
 */
class Redis extends Cache
{
	// 连接实例
    protected $connection;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->connection = new \Redis();
        if (!$this->connection->connect($config['host'], $config['port'] ?? 6379, $config['timeout'] ?? 0)) {
            throw new \Exception('Redis connect error');
        }
        if (isset($config['password'])) {
            $this->connection->auth($config['password']);
        }
        if (isset($config['database'])) {
            $this->connection->select($config['database']);
        }
        if (isset($config['options'])) {
            foreach ($config['options'] as $k => $v) {
                $this->connection->setOption($k, $v);
            }
        }
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
        return $this->connection->exists($key);
    }
	
    /*
     * 设置
     */
    public function set($key, $value, $ttl = null)
    {
        if ($ttl) {
            return $this->connection->setex($key, $ttl, $value);
        } else {
            return $this->connection->set($key, $value); 
        }
    }

    /*
     * 删除
     */
    public function delete($key)
    {
        return $this->connection->del($key);
    }
    
    /*
     * 自增
     */
    public function increment($key, $value = 1)
    {
        return $value > 1 ? $this->connection->incrBy($key, $value) : $this->connection->incr($key);
    }
    
    /*
     * 自减
     */
    public function decrement($key, $value = 1)
    {
        return $value > 1 ? $this->connection->decrBy($key, $value) : $this->connection->decr($key);
    }
    
    /*
     * 清理
     */
    public function clean()
    {
        return $this->connection->flushdb();
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
        $this->connection->close();
    }
}
