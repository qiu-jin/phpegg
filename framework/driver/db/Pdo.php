<?php
namespace framework\driver\db;

abstract class Pdo extends Db
{
    /*
     * 获取dsn
     */
    abstract protected function getDsn($config);
    
    /*
     * 获取表字段名
     */
    abstract protected function getFields($table);
    
	/*
	 * 连接数据库
	 */
    protected function connect($config)
    {
		$dsn = $this->getDsn($config);
        $connection = new \PDO($dsn, $config['username'], $config['password'], $config['options'] ?? null);
        if (isset($config['attributes'])) {
            foreach ($config['attributes'] as $attribute => $value) {
                $connection->setAttribute($attribute, $value);
            }
        }
        return $connection;
    }
	
    /*
     * 读取语句返回一条数据
     */
    public function get($sql, $params = null)
    {
        return ($params ? $this->prepareExecute($sql, $params) : $this->pdoQuery($sql))->fetch(\PDO::FETCH_ASSOC);
    }
    
    /*
     * 读取语句返回全部数据
     */
    public function find($sql, $params = null)
    {
        return ($params ? $this->prepareExecute($sql, $params) : $this->pdoQuery($sql))->fetchAll(\PDO::FETCH_ASSOC);
    }
	
    /*
     * 更新语句返回影响数量
     */
    public function exec($sql, $params = null)
    {
		return $params ? $this->prepareExecute($sql, $params)->rowCount() : $this->execute($sql);
    }
    
    /*
     * 读取语句返回结果对象
     */
    public function query($sql, $params = null)
    {
        return new result\Pdo($params ? $this->prepareExecute($sql, $params) : $this->pdoQuery($sql));
    }
    
    /*
     * 最近插入数据id
     */
    public function insertId()
    {
        return $this->connection->lastInsertId();
    }

    /*
     * 转义字符串
     */
    public function quote($str)
    {
        return $this->connection->quote($str);
    }
    
    /*
     * 开始事务
     */
    public function beginTransaction()
    {
		return $this->connection->beginTransaction();
    }
    
    /*
     * 回滚事务
     */
    public function rollback()
    {
        return $this->connection->rollBack();
    }
    
    /*
     * 提交事务
     */
    public function commit()
    {
		return $this->connection->commit();
    }
    
    /*
     * 获取错误代码
     */
    public function errno()
    {   
		return ($this->connection->errorInfo())[1] ?? null;
    }
	
    /*
     * 获取错误信息
     */
    public function error()
    {   
		return ($this->connection->errorInfo())[2] ?? null;
    }
    
    /*
     * 执行请求
     */
    protected function pdoQuery($sql)
    {
        $this->sqlLog($sql);
        if ($query = $this->connection->query($sql)) {
            return $query;
        }
        throw new \Exception($this->exceptionMessage());
    }
	
	protected function execute($sql)
	{
		$this->sqlLog($sql);
        if ($result = $this->connection->exec($sql)) {
            return $result;
        }
        throw new \Exception($this->exceptionMessage());
	}
    
    /*
     * 预处理执行
     */
    protected function prepareExecute($sql, $params)
    {
        $this->sqlLog($sql, $params);
        if ($query = $this->connection->prepare($sql)) {
            if ($query->execute($params)) {
                return $query;
            }
        }
		$error = $this->connection->errorInfo();
        if ($error[0] === 'HY093') {
            throw new \Exception('DB ERROR: Invalid parameter number');
        }
        throw new \Exception($this->exceptionMessage($error));
    }
    
    /*
     * 异常信息
     */
    protected function exceptionMessage($error = null)
    {
        $err = $error ?? $this->connection->errorInfo();
        return "DB ERROR: [$err[1]] $err[2]";
    }
	
    /*
     * 关闭连接
     */
    public function close()
    {
        $this->connection = null;
    }
}