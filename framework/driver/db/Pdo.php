<?php
namespace framework\driver\db;

use framework\extend\debug\Db as DBDebug;

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
	 * 查询
	 */
    public function select($sql, $params = null)
    {
        $query = $params ? $this->prepareExecute($sql, $params) : $this->connection->query($sql);
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }
    
	/*
	 * 插入
	 */
    public function insert($sql, $params = null, $return_id = false)
    {
        $params ? $this->prepareExecute($sql, $params) : $this->connection->exec($sql);
        if ($return_id) {
            return $this->connection->lastInsertId();
        }
    }
    
	/*
	 * 更新
	 */
    public function update($sql, $params = null)
    {
        if ($params) {
            $this->prepareExecute($sql, $params)->rowCount();
        } else {
            return $this->connection->exec($sql);
        }
    }
    
	/*
	 * 删除
	 */
    public function delete($sql, $params = null)
    {
        return $this->update($sql, $params);
    }
    
    /*
     * 执行sql
     */
    public function exec($sql, $params = null)
    {
        $cmd = trim(strtoupper(strtok($sql, ' ')), "\t(");
        if ($params) {
            $query = $this->prepareExecute($sql, $params);
            switch ($cmd) {
                case 'SELECT':
                    return $query->fetchAll(\PDO::FETCH_ASSOC);
                case 'INSERT':
                    return $this->connection->lastInsertId();
                case 'UPDATE':
                case 'REPLACE':
                case 'DELETE':
                    return $query->rowCount();
                default:
                    return true;
            }
        } else {
            switch ($cmd) {
                case 'SELECT':
                    return $this->realQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);
                case 'INSERT':
                    $this->connection->exec($sql);
                    return $this->connection->lastInsertId();
                case 'UPDATE':
                case 'REPLACE':
                case 'DELETE':
                    return $this->connection->exec($sql);
                default:
                    return (bool) $this->realQuery($sql);
            }
        }
    }
    
    /*
     * 请求sql
     */
    public function query($sql, $params = null)
    {
        return $params ? $this->prepareExecute($sql, $params) : $this->realQuery($sql);
    }
    
    /*
     * 获取一条数据
     */
    public function fetch($query)
    {
        return $query->fetch(\PDO::FETCH_ASSOC);
    }

    /*
     * 获取一条数据（无字段键）
     */
    public function fetchRow($query)
    {
        return $query->fetch(\PDO::FETCH_NUM);
    }
    
    /*
     * 获取所有数据
     */
    public function fetchAll($query)
    {
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /*
     * 获取数据条数
     */
    public function numRows($query)
    {
        return $query->rowCount();
    }
    
    /*
     * 影响数据条数
     */
    public function affectedRows($query)
    {
        return $query->rowCount();
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
    public function begin()
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
     * 获取错误信息
     */
    public function error($query = null)
    {   
        $error = $query ? $query->errorInfo() : $this->connection->errorInfo();
        return array($error[1], $error[2]);
    }
    
    /*
     * 执行请求
     */
    public function realQuery($sql)
    {
        $this->debug && DBDebug::write($sql);
        if ($query = $this->connection->query($sql)) {
            return $query;
        }
        throw new \Exception($this->exceptionMessage());
    }
    
    /*
     * 执行
     */
    public function realExec($sql)
    {
        $this->debug && DBDebug::write($sql);
        if ($result = $this->connection->exec($sql)) {
            return $result;
        }
        throw new \Exception($this->exceptionMessage());
    }
    
    /*
     * 预处理执行
     */
    public function prepareExecute($sql, $params)
    {
        $this->debug && DBDebug::write($sql, $params);
        if ($query = $this->connection->prepare($sql)) {
            if ($query->execute($params)) {
                return $query;
            } else {
                $error = $query->errorInfo();
            }
        } else {
            $error = $this->connection->errorInfo();
        }
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
}