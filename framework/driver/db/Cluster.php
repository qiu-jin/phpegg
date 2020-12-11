<?php
namespace framework\driver\db;

use framework\util\Arr;
use framework\core\Container;

class Cluster
{
	// 工作实例
    protected $work;
	// 读实例
    protected $read;
	// 写实例
    protected $write;
	// 配置
    protected $config;
	// 构造器
    protected $builder;
	// 写入方法集合
    protected static $write_methods = [
        'insertid', 'affectedrows', 'begin', 'rollback', 'commit', 'transaction'
    ];
    
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
	 * 查询
	 */
    public function select($sql, $params = null)
    {
		return $this->selectDatabase()->select($sql, $params);
    }
    
	/*
	 * 插入
	 */
    public function insert($sql, $params = null, $return_id = false)
    {
		return $this->selectDatabase(true)->insert($sql, $params, $return_id);
    }
    
	/*
	 * 更新
	 */
    public function update($sql, $params = null)
    {
		return $this->selectDatabase(true)->update($sql, $params);
    }
    
	/*
	 * 删除
	 */
    public function delete($sql, $params = null)
    {
		return $this->selectDatabase(true)->delete($sql, $params);
    }
    
    /*
     * 执行sql
     */
    public function exec($sql, $params = null)
    {
        return $this->selectDatabase($this->isWrite($sql))->exec($sql, $params);
    }
    
    /*
     * 请求sql
     */
    public function query($sql, $params = null)
    {
        return $this->selectDatabase($this->isWrite($sql))->query($sql, $params);
    }
	
    /*
     * 魔术方法，调用数据库实例方法
     */
    public function __call($method, $params)
    {
        return $this->selectDatabase(in_array(strtolower($method), self::$write_methods))->$method(...$params);
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
    protected function selectDatabase($is_write)
    {
        return $this->makeStickyDatabase($is_write, !empty($this->config['sticky']));
    }
	
    /*
     * 加载数据库实例
     */
    protected function makeDatabase($is_write)
    {
		$type = $is_write ? 'write' : 'read';
        return $this->work = $this->$type ?? ($this->$type = makeDatabaseInstance($type));
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

    /*
     * sql读写类型
     */
    protected function isWrite($sql)
    {
        return strtoupper(substr(ltrim($sql), 0, 6)) !== 'SELECT';
    }
}
