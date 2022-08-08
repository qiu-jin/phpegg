<?php
namespace framework\driver\cache;

use framework\core\Event;

abstract class Cache
{
    // 默认缓存过期时间，0表示永不过期，数组取区间随机值
    protected $ttl = 0;
    
    /*
     * 获取
     */
    abstract public function get($key, $default);
    
    /*
     * 检查
     */
    abstract public function has($key);

    /*
     * 设置
     */
    abstract public function set($key, $value, $ttl);

    /*
     * 删除
     */
    abstract public function delete($key);
    
    /*
     * 自增
     */
    abstract public function increment($key, $value);
    
    /*
     * 自减
     */
    abstract public function decrement($key, $value);
    
    /*
     * 清理
     */
    abstract public function clear();
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
		if (isset($config['ttl'])) {
			$this->ttl = $config['ttl'];
		}
    }
    
    /*
     * 获取并删除
     */
    public function pull($key)
    {
        $value = $this->get($key);
        $this->delete($key);
        return $value;
    }
    
    /*
     * 不存在则设置
     */
    public function remember($key, $value, $ttl = null)
    {
        if (!$this->has($key)) {
            $this->set($key, $value, $ttl);
        }
        return $value;
    }
    
    /*
     * 获取多个
     */
    public function getMultiple(array $keys, $default = null)
    {
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }
    
    /*
     * 设置多个
     */
    public function setMultiple(array $values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }
    
    /*
     * 删除多个
     */
    public function deleteMultiple(array $keys)
    {
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                return false;
            }
        }
        return true;
    }
	
    /*
     * 获取缓存有效时间
     */
    protected function ttl($ttl)
    {
        return is_array($t = $ttl ?? $this->ttl) ? mt_rand($t[0], $t[1]) : $t;
    }
}
