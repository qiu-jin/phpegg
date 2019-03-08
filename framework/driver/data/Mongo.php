<?php
namespace framework\driver\data;

use MongoDB\Driver\Manager;

class Mongo
{
	// 数据库名
    protected $dbname;
	// 原生实例
    protected $manager;
	// 数据库实例集合
    protected $databases;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->manager = new Manager($config['uri'], $config['uri_options'] ?? [], $config['driver_options'] ?? []);
        if (isset($config['dbname'])) {
            $this->dbname = $config['dbname'];
        }
    }
    
    /*
     * 魔术方法，集合实例
     */
    public function __get($name)
    {
        return $this->collection($name);
    }
    
    /*
     * 集合实例
     */
    public function collection($name)
    {
        return new query\Mongo($this->manager, $this->dbname, $name);
    }
    
    /*
     * 数据库实例
     */
    public function db($name)
    {
        if (isset($this->databases[$name])) {
            return $this->databases[$name];
        }
        return $this->databases[$name] = new class ($name, $this->manager) extends Mongo {
            public function __construct($name, $manager) {
                $this->dbname = $name;
                $this->manager = $manager;
            }
            public function db() {
				throw new \Exception('不允许重复调用db方法');
            }
        };
    }
    
    /*
     * 批量请求
     */
    public function batch($collection = null, $options = null)
    {
        return new query\MongoBatch($this->manager, $this->dbname, $collection, $options);
    }
    
    /*
     * 获取原生实例
     */
    public function getManager()
    {
        return $this->manager;
    }
}
