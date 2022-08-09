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
    // 数据库实例
    protected $db;
    // 数据表名
    protected $table = 'cache';
    // 数据表字段名
    protected $fields = ['key', 'value', 'expiration'];
    // 序列化反序列化处理器
    protected $serializer = ['serialize', 'unserialize'];

    /*
     * 初始化
     */
    public function __construct($config)
	{
		parent::__construct($config);
		if (isset($config['table'])) {
			$this->table = $config['table'];
		}
		if (isset($config['fields'])) {
			$this->fields = $config['fields'];
		}
		if (isset($config['serializer'])) {
			$this->serializer = $config['serializer'];
		}
		$this->db = Container::driver('db', $config['db']);
    }
    
    /*
     * 获取
     */
    public function get($key, $default = null)
    {
        $cache = $this->db->get($this->format(
            'SELECT %s FROM %s WHERE %s = ? AND %s > '.time(), $this->fields[1], $this->table, $this->fields[0], $this->fields[2]
        ), [$key]);
        return $cache ? ($this->serializer[1])($cache[0]['value']) : $default;
    }

    /*
     * 检查
     */
    public function has($key)
    {
        return $this->db->query($this->format(
            'SELECT %s FROM %s WHERE %s = ? AND %s > '.time(), $this->fields[1], $this->table, $this->fields[0], $this->fields[2]
        ), [$key])->count() > 0;
    }
    
    /*
     * 设置
     */
    public function set($key, $value, $ttl = null)
    {
        return (bool) $this->db->exec($this->format(
            'REPLACE INTO %s SET %s = ?, %s = ?, %s = ?', $this->table, ...$this->fields
        ), [
            $key, ($this->serializer[0])($value), time() + (($t = $this->ttl($ttl)) == 0 ? $this->gc_maxlife : $t)
        ]);
    }

    /*
     * 删除
     */
    public function delete($key)
    {
        return (bool) $this->db->exec($this->format('DELETE FROM %s WHERE %s = ?', $this->table, $this->fields[0]), [$key]);
    }
	
    /*
     * 自增
     */
    public function increment($key, $value = 1)
    {
        return $this->set($key, $this->get($key, 0) + $value);
    }
    
    /*
     * 自减
     */
    public function decrement($key, $value = 1)
    {
        return $this->set($key, $this->get($key, 0) - $value);
    }
    
    /*
     * 获取多个
     */
    public function getMultiple(array $keys, $default = null)
    {
        $in = implode(',', array_fill(0, count($keys), '?'));
        $reslut = $this->db->find($this->format(
            "SELECT %s, %s FROM %s WHERE %s IN ($in) AND %s > ".time(),
            $this->fields[0], $this->fields[1], $this->table, $this->fields[0], $this->fields[2]
        ), $keys);
        $caches = array_column($reslut, 'value', 'key');
        foreach ($keys as $key) {
            $caches[$key] = isset($caches[$key]) ? ($this->serializer[1])($caches[$key]) : $default;
        }
        return $caches;
    }
    
    /*
     * 删除多个
     */
    public function deleteMultiple(array $keys)
    {
        $in = implode(',', array_fill(0, count($keys), '?'));
        return (bool) $this->db->exec($this->format("DELETE FROM %s WHERE %s IN ($in)", $this->table. $this->fields[0]), $keys);
    }

    /*
     * 清理
     */
    public function clear()
    {
        return (bool) $this->db->exec($this->format('TRUNCATE %s', $this->table));
    }
    
    /*
     * 垃圾回收
     */
    public function gc()
    {
        $this->db->exec($this->format('DELETE FROM %s WHERE %s < '.time(), $this->table, $this->fields[2]));
    }
    
    /*
     * sql格式化
     */
    protected function format($sql, ...$params)
    {
        return sprintf($sql, ...array_map(function ($v) {
			return ($this->db::Builder)::keywordEscape($v);
		}, $params));
    }
}
