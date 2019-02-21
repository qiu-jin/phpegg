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
	 * 查询
	 */
    public function select($sql, $params = null)
    {
        $query = $params ? $this->prepareExecute($sql, $params)->get_result() : $this->connection->query($sql);
        return $query->fetch_all(MYSQLI_ASSOC);
    }
    
	/*
	 * 插入
	 */
    public function insert($sql, $params = null, $return_id = false)
    {
        if ($params) {
            $query = $this->prepareExecute($sql, $params);
            if ($return_id) {
                return $query->insert_id; 
            }
        } else {
            $this->connection->query($sql);
            if ($return_id) {
                return $this->connection->insert_id; 
            }
        }
    }
    
	/*
	 * 更新
	 */
    public function update($sql, $params = null)
    {
        if ($params) {
            return $this->prepareExecute($sql, $params)->affected_rows;
        } else {
            $this->connection->query($sql);
            $this->connection->affected_rows; 
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
    public function exec($sql, array $params = null, $is_assoc = false)
    {
        $cmd = trim(strtoupper(strtok($sql, ' ')),"\t(");
        if ($params) {
            $query = $this->prepareExecute($sql, $params, $is_assoc);
            switch ($cmd) {
                case 'SELECT':
                    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
                case 'INSERT':
                    return $query->insert_id;
                case 'UPDATE':
                case 'REPLACE':
                case 'DELETE':
                    return $query->affected_rows;
                default:
                    return true;
            }
        } else {
            $query = $this->realQuery($sql);
            switch ($cmd) {
                case 'SELECT':
                    return $query->fetch_all(MYSQLI_ASSOC);
                case 'INSERT':
                    return $this->connection->insert_id;
                case 'UPDATE':
                case 'REPLACE':
                case 'DELETE':
                    return $this->connection->affected_rows;
                default:
                    return (bool) $query;
            }
        }
    }
    
    /*
     * 请求sql
     */
    public function query($sql, array $params = null, $is_assoc = false)
    {
        if ($params) {
            return $this->prepareExecute($sql, $params, $is_assoc)->get_result();
        } else {
            return $this->realQuery($sql);
        }
    }
    
    /*
     * 切换数据库
     */
    public function switch($dbname, callable $call)
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
     * 获取一条数据
     */
    public function fetch($query)
    {
        return $query->fetch_assoc();
    }
    
    /*
     * 获取一条数据（无字段键）
     */
    public function fetchRow($query)
    {
        return $query->fetch_row();
    }
    
    /*
     * 获取所有数据
     */
    public function fetchAll($query)
    {
        return $query->fetch_all(MYSQLI_ASSOC);
    }
    
    /*
     * 获取数据条数
     */
    public function numRows($query)
    {
        return $query->num_rows;
    }
    
    /*
     * 影响数据条数
     */
    public function affectedRows($query = null)
    {
        return $query ? $query->affected_rows : $this->connection->affected_rows;
    }
    
    /*
     * 最近插入数据id
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
    public function begin()
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
     * 获取错误信息
     */
    public function error($query = null)
    {
        $q = $query ?? $this->connection;
        return array($q->errno, $q->error);
    }
    
    /*
     * 获取表字段名
     */
    public function getFields($table)
    {
        return array_column($this->select("desc `$table`"), 'Field');
    }
    
    /*
     * 执行请求
     */
    public function realQuery($sql)
    {
        $this->debug && $this->log($sql);
        if ($query = $this->connection->query($sql)) {
            return $query;
        }
        throw new \Exception($this->exceptionMessage());
    }
    
    /*
     * 预处理执行
     */
    public function prepareExecute($sql, $params, $is_assoc = false)
    {
        $this->debug && $this->log($sql, $params, $is_assoc);
        $bind_params = [];
        if ($is_assoc) {
            if (preg_match_all('/\:(\w+)/', $sql, $matchs, PREG_OFFSET_CAPTURE)) {
                $str = '';
                $start = 0;
                foreach ($matchs[0] as $i => $match) {
                    $str .= substr($sql, $start, $match[1]-$start).'?';
                    $bind_params[] = &$params[$matchs[1][$i][0]];
                    $start = strlen($match[0]) + $match[1];
                }
                if ($start < strlen($sql)) {
                    $str .= substr($sql, $start);
                }
                $sql = $str;
            }
        } else {
            foreach ($params as $k => $v) {
                $bind_params[] = &$params[$k];
            }
        }
        if ($query = $this->connection->prepare($sql)) {
            $type  = str_pad('', count($bind_params), 's');
            array_unshift($bind_params, $type);
            $query->bind_param(...$bind_params);
            if ($query->execute()) {
                return $query;
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
     * 析构函数
     */
    public function __destruct()
    {
        $this->connection->close();
    }
}
