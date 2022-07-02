<?php
namespace framework\driver\db\result;

class Mysqli
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
        return $this->query->fetch_assoc();
    }
    
    /*
     * 获取一条数据（无字段键）
     */
    public function fetchRow()
    {
        return $this->query->fetch_row();
    }
    
    /*
     * 获取所有数据
     */
    public function fetchAll()
    {
        return $this->query->fetch_all(MYSQLI_ASSOC);
    }
    
    /*
     * 获取查询到的数据条数
     */
    public function count()
    {
        return $this->query->num_rows;
    }

    /*
     * 获取错误代码
     */
    public function errno()
    {   
		return $this->query->errno;
    }
    
    /*
     * 获取错误信息
     */
    public function error()
    {
		return $this->query->error;
    }

}