<?php
namespace framework\driver\db;

use framework\util\Str;
use framework\core\Logger;
use framework\core\Container;

abstract class Db
{
    // SQL纪录
    protected $sql;
    // 调试模式
    protected $debug = \app\env\APP_DEBUG;
    // 数据库名
    protected $dbname;
    // 数据库连接
    protected $connection;
    // 数据库表字段
    protected $fields;
    // 数据库表字段缓存实例
    protected $fields_cache;
    // 数据库表字段缓存实例配置
    protected $fields_cache_config;
    
    /*
     * 执行SQL返回结果
     */
    abstract public function exec($sql);
    
    /*
     * 执行SQL返回query
     */
    abstract public function query($sql);
    
    /*
     * 获取一条数据
     */
    abstract public function fetch($query);
    
    /*
     * 获取一条数据（无字段键）
     */
    abstract public function fetchRow($query);
    
    /*
     * 获取所有数据
     */
    abstract public function fetchAll($query);
    
    /*
     * 获取数据条数
     */
    abstract public function numRows($query);
    
    /*
     * 影响数据条数
     */
    abstract public function affectedRows($query);
    
    /*
     * 最近插入数据ID
     */
    abstract public function insertId();
    
    /*
     * 开始事务
     */
    abstract public function beginTransaction();
    
    /*
     * 回滚事务
     */
    abstract public function rollback();
    
    /*
     * 提交事务
     */
    abstract public function commit();
    
    /*
     * 转义字符串
     */
    abstract public function quote($str);
    
    /*
     * 获取错误代码
     */
    abstract public function errno();
	
    /*
     * 获取错误信息
     */
    abstract public function error();
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->connection = $this->connect($config);
        $this->dbname = $config['dbname'];
        if (isset($config['debug'])) {
            $this->debug = $config['debug'];
        }
        if (isset($config['fields_cache'])) {
            $this->fields_cache_config = $config['fields_cache'];
        }
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
     * 执行事务
     */
    public function transaction(callable $call)
    {
        try {
            $this->beginTransaction();
            ($return = $call($this)) === false ? $this->commit() : $this->rollback();
            return $return;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /*
     * 获取表字段
     */
    public function fields($table)
    {
        if (isset($this->fields[$this->dbname][$table])) {
            return $this->fields[$this->dbname][$table];
        } else {
            if (isset($this->fields_cache_config)) {
				$cache = $this->fields_cache ??
					     $this->fields_cache = Container::driver('cache', $this->fields_cache_config);
                if ($fields = $cache->get($key = "$this->dbname-$table")) {
                    return $fields;
                }
            }
            $fields = $this->getFields($table);
            if (isset($cache)) {
                $cache->set($key, $fields);
            }
            return $this->fields[$this->dbname][$table] = $fields;
        }
    }
    
    /*
     * 设置调试模式
     */
    public function debug($bool = true)
    {
        $this->debug = $bool;
    }
    
    /*
     * 获取数据Builder类
     */
    public function getBuilder()
    {
        return static::BUILDER;
    }
    
    /*
     * 获取数据库连接
     */
    public function getConnection()
    {
        return $this->connection;
    }
	
    /*
     * 日志
     */
    protected function log($sql, $params = null)
    {
        if ($params) {
            if (isset($params[0])) {
                $sql = vsprintf(str_replace("?", "'%s'", $sql), $params);
            } else {
                $sql = Str::format($sql, $params, ':%s');
            }
        }
		Logger::channel($this->debug)->debug($sql);
    }
}