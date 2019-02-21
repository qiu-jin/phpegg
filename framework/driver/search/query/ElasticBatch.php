<?php
namespace framework\driver\search\query;

use framework\core\http\Client;

class ElasticBatch
{
	// 获取类型设置集合
    protected $mget;
	// 修改类型设置集合
    protected $bulk;
	// 类型(7.x版本中去除)
    protected $type;
	// 索引
    protected $index;
	// 服务端点
    protected $endpoint;
	// 公共索引
    protected $common_index;
    
    /*
     * 构造函数
     */
    public function __construct($endpoint, $index, $type)
    {
        $this->type = $type;
        $this->endpoint = $endpoint;
        $this->common_index = $index;
    }
    
    /*
     * 魔术方法，设置index
     */
    public function __get($name)
    {
        if (!isset($this->index)) {
            $this->index = $name;
            return $this;
        }
        throw new \Exception('Index has been set');
    }
    
    /*
     * 获取
     */
    public function get($id, $options = [])
    {
        $this->mget[] = array_merge($options, $this->getQuery($id));
        return $this;
    }
    
    /*
     * 设置
     */
    public function set($id, $data)
    {
        $this->bulk[] = json_encode(['index' => $this->getQuery($id)]);
        $this->bulk[] = json_encode($data);
        return $this;
    }
    
    /*
     * 创建
     */
    public function create($data)
    {
        $this->bulk[] = json_encode(['index' => $this->getQuery()]);
        $this->bulk[] = json_encode($data);
        return $this;
    }
    
    /*
     * 更新
     */
    public function update($id, $data)
    {
        $this->bulk[] = json_encode(['update' => $this->getQuery($id)]);
        $this->bulk[] = json_encode($data);
        return $this;
    }
    
    /*
     * 删除
     */
    public function delete($id)
    {
        $this->bulk[] = json_encode(['delete' => $this->getQuery($id)]);
        return $this;
    }
    
    /*
     * 调用
     */
    public function call()
    {
        if (isset($this->bulk)) {
            if (isset($this->mget)) {
                throw new \Exception('No support read and write mix');
            }
            $method = '_bulk';
            $body = implode("\n", $this->bulk)."\n";
        } elseif (isset($this->mget)) {
            if (isset($this->bulk)) {
                throw new \Exception('No support read and write mix');
            }
            $method = '_mget';
            $body = json_encode(['docs' => $this->mget]);
        } else {
            throw new \Exception('Query is empty');
        }
        $client = Client::post("$this->endpoint/$method")->body($body);
        $response = $client->response();
        if ($response->status >= 200 && $response->status < 300) {
            return $response->json();
        }
        error($client->error);
    }
    
    /*
     * 获取请求设置
     */
    protected function getQuery($id = null)
    {
        if ($this->index) {
            $index = $this->index;
            $this->index = null;
            return $query = ['_index' => $index, '_type' => $this->type];
        } elseif ($this->common_index) {
            return $query = ['_index' => $this->common_index, '_type' => $this->type];
        } else {
            throw new \Exception('Index is empty');
        }
        if (isset($id)) {
            $query['_id'] = $id;
        }
        return $query;
    }
}
