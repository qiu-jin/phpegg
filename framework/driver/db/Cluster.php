<?php
namespace framework\driver\db;

use framework\util\Arr;
use framework\core\Container;

class Cluster
{
	// 现实例
    protected $work;
	// 读实例
    protected $read;
	// 写实例
    protected $write;
	// 配置
    protected $config;
	// 构造器
    protected $builder;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->builder = (__NAMESPACE__.'\\'.ucfirst($config['dbtype']))::BUILDER;
    }
    
    /*
     * 魔术方法，query实例
     */
    public function __get($name)
    {
        return $this->table($name);
    }

    /*
     * query实例
     */
    public function table($name)
    {
        return new query\Query($this, $name);
    }
	
    /*
     * 读取语句返回全部数据
     */
    public function all($sql, $params = null)
    {
		return $this->selectDatabase()->all($sql, $params);
    }
    
    /*
     * 更新语句返回影响数量
     */
    public function exec($sql, $params = null)
    {
        return $this->selectDatabase(true)->exec($sql, $params);
    }
    
    /*
     * 读取语句返回结果对象
     */
    public function query($sql, $params = null)
    {
        return $this->selectDatabase()->query($sql, $params);
    }
	
    /*
     * 魔术方法，调用数据库实例方法
     */
    public function __call($method, $params)
    {
		$is_write_method = in_array(strtolower($method), ['insertid', 'begintransaction', 'rollback', 'commit', 'transaction']);
        return $this->selectDatabase($is_write_method)->$method(...$params);
    }
    
    /*
     * 获取构造器
     */
    public function getBuilder()
    {
        return $this->builder;
    }
    
    /*
     * 获取数据库实例
     */
    public function getDatabase($is_write = null, $sticky = false)
    {
		if ($is_write === null && $sticky && $this->write) {
			return $this->work = $this->write;
		}
		return $this->makeDatabase($is_write);
    }
	
    /*
     * 选择数据库实例
     */
    protected function selectDatabase($is_write = false)
    {
        return $this->makeStickyDatabase($is_write, !empty($this->config['sticky']));
    }
	
    /*
     * 加载数据库实例
     */
    protected function makeDatabase($is_write)
    {
		$type = $is_write ? 'write' : 'read';
        return ($this->work = $this->$type) ?? ($this->$type = makeDatabaseInstance($type));
    }
	
    /*
     * 选择数据库实例
     */
    protected function makeStickyDatabase($is_write, $sticky)
    {
		return $sticky && $this->write ? ($this->work = $this->write) : $this->makeDatabase($is_write);
    }
	
    /*
     * 生成数据库实例
     */
    protected function makeDatabaseInstance($type)
    {
		$config = Arr::random($this->config[$type]);
		$config['driver'] = $this->config['dbtype'];
        return Container::driver('db', $config + $this->config);
    }
}
