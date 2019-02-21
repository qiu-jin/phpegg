<?php
namespace framework\driver\db;

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
        'insert', 'update', 'delete', 'insertid', 'affectedrows', 'begin', 'rollback', 'commit', 'transaction'
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
     * 执行sql
     */
    public function exec($sql, $params = null)
    {
        return $this->selectDatabase($this->sqlType($sql))->exec($sql, $params);
    }
    
    /*
     * 请求sql
     */
    public function query($sql, $params = null)
    {
        return $this->selectDatabase($this->sqlType($sql))->query($sql, $params);
    }
	
    /*
     * 魔术方法，调用数据库实例方法
     */
    public function __call($method, $params)
    {
        $m = strtolower($method);
        return $this->getDatabase(in_array($m, self::$write_methods) ? 'write' : null)->$method(...$params);
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
    public function getDatabase($type = null, $sticky = true)
    {
        if ($type == 'write' || $type == 'read') {
            return $this->selectDatabase($type, $sticky);
        }
        return $this->work ?? $this->selectDatabase('read', $sticky);
    }
    
    /*
     * 选择数据库实例
     */
    protected function selectDatabase($type, $sticky = true)
    {
        if (!empty($this->config['sticky']) && $sticky && $this->write) {
            return $this->work = $this->write;
        }
        return $this->work = $this->$type ?? (
			$this->$type = Container::makeDriverInstance('db', ['driver' => $this->config['dbtype']] + $this->config[$type])
        );
    }
    
    /*
     * sql读写类型
     */
    protected function sqlType($sql)
    {
        return strtoupper(substr(ltrim($sql), 0, 6)) == 'SELECT' ? 'read' : 'write';
    }
}
