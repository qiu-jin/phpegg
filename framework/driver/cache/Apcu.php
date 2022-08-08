<?php
namespace framework\driver\cache;

/*
 * http://php.net/apcu
 */
class Apcu extends Cache
{
	// 字段前缀
    protected $prefix;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
		parent::__construct($config);
        $this->prefix = $config['prefix'];
    }
    
    /*
     * 获取
     */
    public function get($key, $default = null)
    {
        return apcu_fetch($this->prefix.$key) ?? $default;
    }

    /*
     * 检查
     */
    public function has($key)
    {
        return apcu_exists($this->prefix.$key);
    }
	
    /*
     * 设置
     */
    public function set($key, $value, $ttl = null)
    {
        return apcu_store($this->prefix.$key, $value, $this->ttl($ttl));
    }
	
    /*
     * 删除
     */
    public function delete($key)
    {
        return apcu_delete($this->prefix.$key);
    }
    
    /*
     * 自增
     */
    public function increment($key, $value = 1)
    {
        return apcu_inc($this->prefix.$key, $value);
    }
    
    /*
     * 自减
     */
    public function decrement($key, $value = 1)
    {
        return apcu_dec($this->prefix.$key, $value);
    }
    
    /*
     * 清理
     */
    public function clear()
    {
		return apcu_clear_cache();
    }
}
