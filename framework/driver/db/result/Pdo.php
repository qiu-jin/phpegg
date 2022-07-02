<?php
namespace framework\driver\db\result;

class Pdo
{
	// 原始query
    protected $query;
	
    /*
     * 构造函数
     */
    public function __construct($query)
    {
        $this->query = $query;
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
     * 获取所有数据
     */
    public function fetchAll()
    {
        return $this->query->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /*
     * 获取数据条数
     */
    public function count()
    {
        return $this->query->rowCount();
    }
    
    /*
     * 影响数据条数
     */
    public function affectedCount()
    {
        return $this->query->rowCount();
    }

    /*
     * 获取错误代码
     */
    public function errno()
    {   
		return ($this->query->errorInfo())[1] ?? null;
    }
	
    /*
     * 获取错误信息
     */
    public function error()
    {   
		return ($this->query->errorInfo())[2] ?? null;
    }

}