<?php
namespace framework\driver\data\query;

use MongoDB\Driver\BulkWrite;

class MongoBatch
{
	// 资源ns
    protected $ns;
	// 操作集合
    protected $bulk;
	// 查找条件
    protected $where;
	// 原生实例
    protected $manager;
	// 设置项
    protected $options;
    
    /*
     * 构造函数
     */
    public function __construct($manager, $db, $collection, $options = null)
    {
        $this->manager = $manager;
        $this->ns = "$db.$collection";
        $this->bulk = new BulkWrite($options);
    }
    
    /*
     * 设置
     */
    public function set($id, $data)
    {
        return $this->bulkWrite('update', ['_id' => $id], $data, ['upsert' => true]);
    }

    /*
     * 插入
     */
    public function insert($data)
    {
        return $this->bulkWrite('insert', $data);
    }
    
    /*
     * 更新
     */
    public function update($data)
    {
       return $this->bulkWrite('update', $this->where, $data, $this->options);
    }
    
    /*
     * 删除
     */
    public function delete($id = null)
    {
        return $this->bulkWrite('delete', $id ? ['_id' => $id] : $this->where, $this->options);
    }
    
    /*
     * 设置查找条件
     */
    public function where($where)
    {
        $this->where = $where;
        return $this;
    }
    
    /*
     * 结果限制
     */
    public function limit($limit, $skip = null)
    {
        $this->options['limit'] = $limit;
        if ($skip) {
            $this->options['skip'] = $skip;
        }
        return $this;
    }
    
    /*
     * 自定义设置项
     */
    public function options($options)
    {
        $this->options = $this->options ? array_merge($this->options, $options) : $options;
        return $this;
    }

    /*
     * 调用
     */
    public function call()
    {
        return $this->manager->executeBulkWrite($this->ns, $this->bulk);
    }
    
    /*
     * 执行写操作
     */
    protected function bulkWrite($method, ...$params)
    {
        $bulk->$method(...$params);
        $this->where = $this->options = null;
        return $this;
    }
}
