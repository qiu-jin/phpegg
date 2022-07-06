<?php
namespace framework\driver\db\result;

class Pdo
{
	// 原始query
    protected $query;
	
    /*
     * 构造函数
     */
    public function __construct(\PDOStatement $query)
    {
        $this->query = $query;
    }
	
    /*
     * 获取数据条数
     */
    public function count()
    {
        return $this->query->rowCount();
    }
	
    /*
     * 获取一条数据
     */
    public function fetch()
    {
        return $this->query->fetch(\PDO::FETCH_ASSOC);
    }

    /*
     * 获取一条数据（无字段键）
     */
    public function fetchRow()
    {
        return $this->query->fetch(\PDO::FETCH_NUM);
    }
	
    /*
     * 获取一条数据（object）
     */
    public function fetchObject($class_name = 'stdClass', array $ctor_args = null)
    {
        return $this->query->fetchObject($class_name, $ctor_args);
    }
    
    /*
     * 获取所有数据
     */
    public function fetchAll()
    {
        return $this->query->fetchAll(\PDO::FETCH_ASSOC);
    }
}