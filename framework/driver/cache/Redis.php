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
		$this->connection = $this->contect($config);
    }
	
    /*
     * 连接
     */
    protected function contect($config)
    {
        $connection = new \Redis();
        if (!$connection->connect($config['host'], $config['port'] ?? 6379, $config['timeout'] ?? 0)) {
            throw new \Exception('Redis connect error');
        }
        if (isset($config['password'])) {
            $connection->auth($config['password']);
        }
        if (isset($config['database'])) {
            $connection->select($config['database']);
        }
        if (isset($config['options'])) {
            foreach ($config['options'] as $k => $v) {
                $connection->setOption($k, $v);
            }
        }
		return $connection;
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
        if (($t = $ttl ?? $this->ttl) == 0) {
            return $this->connection->set($key, $value);
        } else {
			return $this->connection->setex($key, $t, $value);
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
     * 关闭连接
     */
    public function close()
    {
       $this->connection->close();
    }
    
    /*
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }
}
