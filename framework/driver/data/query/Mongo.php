<?php
namespace framework\driver\data\query;

use MongoDB\Driver\Query;
use MongoDB\Driver\Command;
use MongoDB\Driver\BulkWrite;

class Mongo
{
	// 数据库名
    protected $db;
	// 是否返回原始结果
    protected $raw;
	// 查找条件
    protected $where;
	// 原生实例
    protected $manager;
	// 设置项
    protected $options;
	// 集合名
    protected $collection;
    
    /*
     * 构造函数
     */
    public function __construct($manager, $db, $collection)
    {
		$this->db = $db;
        $this->manager = $manager;
        $this->collection = $collection;
    }
    
    /*
     * 查询（单条）
     */
    public function get($id)
    {
		$this->where = ['_id' => $id];
        return $this->find(1)[0] ?? null;
    }
    
    /*
     * 查询（多条）
     */
    public function find($limit = 0)
    {
        if ($limit > 0) {
            $this->options['limit'] = $limit;
        }
        $result = $this->manager->executeQuery("$this->db.$this->collection", new Query($this->where, $this->options));
        return $this->raw ? $result : $result->toArray();
    }
    
    /*
     * 设置
     */
    public function set($id, $data)
    {
        $result = $this->bulkWrite('update', ['_id' => $id], $data, ['upsert' => true]);
        return $this->raw ? $result : ($result->getUpsertedCount() ?: $result->getModifiedCount());
    }

    /*
     * 插入
     */
    public function insert($data)
    {
        $result = $this->bulkWrite('insert', $data);
        return $this->raw ? $result : $result->getInsertedCount();
    }
    
    /*
     * 更新
     */
    public function update($data)
    {
        $result = $this->bulkWrite('update', $this->where, $data, $this->options);
        return $this->raw ? $result : $result->getModifiedCount();
    }
    
    /*
     * 删除
     */
    public function delete($id = null)
    {
        $result = $this->bulkWrite('delete', $id ? ['_id' => $id] : $this->where, $this->options);
        return $this->raw ? $result : $result->getDeletedCount();
    }
	
    /*
     * 数量
     */
    public function count(array $where = null)
    {
		$cmd = ['count' => $this->collection, 'query' => $where ?? $this->where];
        $result = $this->manager->executeCommand($this->db, new Command($cmd), $this->options);
        return $this->raw ? $result : ($result->toArray()[0]->n ?? 0);
    }

    /*
     * 设置是否返回原始结果
     */
    public function raw($bool = true)
    {
        $this->raw = (bool) $bool;
        return $this;
    }
    
    /*
     * 设置查询字段
     */
    public function select(...$fields)
    {
        $this->options['projections'] = $fields;
        return $this;
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
     * 结果排序
     */
    public function order($field, $desc = false)
    {
        $this->options['sort'][$field] = $desc ? -1 : 1;
        return $this;
    }
    
    /*
     * 自定义设置项
     */
    public function options(array $options)
    {
        $this->options = $this->options ? $options + $this->options : $options;
        return $this;
    }
    
    /*
     * 执行写操作
     */
    protected function bulkWrite($method, ...$params)
    {
        $bulk = new BulkWrite;
        $bulk->$method(...$params);
        return $this->manager->executeBulkWrite("$this->db.$this->collection", $bulk);
    }
}
