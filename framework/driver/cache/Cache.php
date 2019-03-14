<?php
namespace framework\driver\cache;

use framework\core\Event;

abstract class Cache
{
    // 默认缓存过期时间
    protected $ttl = 0;
    // 序列化反序列化处理器
    protected $serializer;
    // 垃圾回收处理生命周期（部分驱动有效）
    protected $gc_maxlife = 2592000;
    
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
    abstract public function clean();
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->__init($config);
		if (isset($config['ttl'])) {
			$this->ttl = $config['ttl'];
		}
		if (isset($config['serializer'])) {
			$this->serializer = $config['serializer'];
		}
        if (isset($config['gc_random'])
            && method_exists($this, 'gc')
            && mt_rand(0, $config['gc_random'][1]) < $config['gc_random'][0]
        ) {
			if (isset($config['gc_maxlife'])) {
				$this->gc_maxlife = $config['gc_maxlife'];
			}
            Event::on('close', [$this, 'gc']);
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
     * 序列化
     */
    protected function serialize($data)
    {
        return $this->serializer ? ($this->serializer[0])($data) : $data;
    }
    
    /*
     * 反序列化
     */
    protected function unserialize($data)
    {
        return $this->serializer ? ($this->serializer[1])($data) : $data;
    }
}
