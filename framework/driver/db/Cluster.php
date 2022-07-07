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
		return $this->selectDatabase(false)->all($sql, $params);
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
        return $this->selectDatabase(false)->query($sql, $params);
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
    public function getDatabase($mode = null)
    {
		switch ($mode) {
			case null:
				return $this->work;
			case true:
				return $this->write ?? $this->read;
			case 'read':
			case 'write':
				return $this->$mode ?? $this->makeDatabase($mode);
		}
		throw new \Exception("Invalid cluster database mode: $mode");
    }
	
    /*
     * 魔术方法，调用数据库实例方法
     */
    public function __call($method, $params)
    {
		$m = strtolower($method);
		if (in_array($m, ['insertid', 'begintransaction', 'rollback', 'commit', 'transaction'])) {
			$is_write = true;
		} elseif ($m == 'fields') {
			$is_write = false;
		}
		// 其他quote, errno, error, debug, getConnection
        return $this->selectDatabase($is_write ?? null)->$method(...$params);
    }
	
    /*
     * 选择数据库实例
     */
    protected function selectDatabase($is_write = null)
    {
		if (isset($is_write)) {
			if ($is_write) {
				$db = $this->write ?? $this->makeDatabase('write');
			} elseif (!empty($this->config['sticky'] && $this->write)) {
				$db = $this->write;
			} else {
				$db = $this->read ?? $this->makeDatabase('read');
			}
			return $this->work = $db;
		}
		return $this->work ?? $this->work = ($this->read ?? $this->write ?? $this->makeDatabase('read'));
	}
	
    /*
     * 生成数据库实例
     */
    protected function makeDatabase($mode)
    {
		$config = Arr::random($this->config[$mode]);
		$config['driver'] = $this->config['dbtype'];
        return $this->$mode = Container::driver('db', $config + $this->config);
    }
}
