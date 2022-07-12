<?php
namespace framework\driver\db;

class Mysqli extends Db
{
	// 构造器
    const BUILDER = builder\Builder::class;
    
	/*
	 * 连接数据库
	 */
    protected function connect($config)
    {
        $connection = new \mysqli(
            $config['host'], 
            $config['username'], 
            $config['password'], 
            $config['dbname'], 
            $config['port'] ?? '3306', 
            $config['socket'] ?? null
        );
        if ($connection->connect_error) {
            throw new \Exception("Server Connect Error [$connection->connect_errno] $connection->connect_error");
        }
        if (isset($config['charset'])) {
            $connection->set_charset($config['charset']);
        }
        if (isset($config['options'])) {
            foreach ($config['options'] as $option => $value) {
                $connection->options($option, $value);
            }
        }
        return $connection;
    }
	
    /*
     * 读取语句返回一条数据
     */
    public function get($sql, $params = null)
    {
        $query = $params ? $this->prepareExecute($sql, $params, true) : $this->execute($sql);
        $return = $query->fetch_assoc();
		$query->free();
		return $return;
    }
	
    /*
     * 读取语句返回全部数据
     */
    public function find($sql, $params = null)
    {
        $query = $params ? $this->prepareExecute($sql, $params, true) : $this->execute($sql);
        $return = $query->fetch_all(MYSQLI_ASSOC);
		$query->free();
		return $return;
    }
    
    /*
     * 更新语句返回影响数量
     */
    public function exec($sql, $params = null)
    {
		$params ? $this->prepareExecute($sql, $params) : $this->execute($sql);
		return $this->connection->affected_rows;
    }
    
    /*
     * 读取语句返回结果对象
     */
    public function query($sql, $params = null)
    {
		return new result\Mysqli($params ? $this->prepareExecute($sql, $params, true) : $this->execute($sql, MYSQLI_USE_RESULT));
    }

    /*
     * 获取最近插入数据的ID
     */
    public function insertId()
    {
        return $this->connection->insert_id;
    }

    /*
     * 转义字符串
     */
    public function quote($str)
    {
        return "'".$this->connection->escape_string($str)."'";
    }
    
    /*
     * 开始事务
     */
    public function beginTransaction()
    {
		return $this->connection->autocommit(false) && $this->connection->begin_transaction();
    }
    
    /*
     * 回滚事务
     */
    public function rollback()
    {
        return $this->connection->rollback() && $this->connection->autocommit(true);
    }
    
    /*
     * 提交事务
     */
    public function commit()
    {
		return $this->connection->commit() && $this->connection->autocommit(true);
    }
	
    /*
     * 获取错误代码
     */
    public function errno()
    {   
		return $this->connection->errno;
    }
    
    /*
     * 获取错误信息
     */
    public function error()
    {
		return $this->connection->error;
    }
    
    /*
     * 获取表字段名
     */
    public function getFields($table)
    {
        return array_column($this->select("desc `$table`"), 'Field');
    }
    
    /*
     * 切换使用数据库
     */
    public function useDatabase($dbname, callable $call)
    {
        $raw_dbname = $this->dbname;
        try {
            if ($this->connection->select_db($dbname)) {
                $this->dbname = $dbname;
                return $call($this);
            }
        } finally {
            $this->dbname = $raw_dbname;
            $this->connection->select_db($raw_dbname);
        }
    }
	
    /*
     * 执行请求
     */
	protected function execute($sql, $mode = MYSQLI_STORE_RESULT)
	{
        $this->sqlLog($sql);
        if ($result = $this->connection->query($sql, $mode)) {
			return $result;
        }
        throw new \Exception($this->exceptionMessage());
	}
    
    /*
     * 预处理执行
     */
    protected function prepareExecute($sql, $params, $return_result = false)
    {
        $this->sqlLog($sql, $params);
        $bind_params = [];
        foreach ($params as $k => $v) {
            $bind_params[] = &$params[$k];
        }
        if ($query = $this->connection->prepare($sql)) {
            $type = str_pad('', count($bind_params), 's');
            array_unshift($bind_params, $type);
            $query->bind_param(...$bind_params);
            if ($query->execute()) {
				if ($return_result) {
					$result = $query->get_result();
					$query->close();
					return $result;
				}
                $query->close();
            }
        }
        throw new \Exception($this->exceptionMessage());
    }
    
    /*
     * 异常信息
     */
    protected function exceptionMessage()
    {
        return 'DB ERROR: ['.$this->connection->errno.'] '.$this->connection->error;
    }
	
    /*
     * 关闭连接
     */
    public function close()
    {
		if (isset($this->connection)) {
			$this->connection->close();
			$this->connection = null;
		}
    }

    /*
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }
}
