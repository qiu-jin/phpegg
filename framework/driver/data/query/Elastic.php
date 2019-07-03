<?php
namespace framework\driver\data\query;

use framework\core\http\Client;

class Elastic
{
	// 是否返回原始结果
    protected $raw;
	// 服务端点
    protected $endpoint;
    
    /*
     * 构造函数
     */
	public function __construct($url, $index, $type)
    {
        $this->endpoint = "$url/$index/$type";
    }
    
    /*
     * 获取
     */
    public function get($id, $options = null)
    {
        return $this->result($this->call('GET', $id, $options), '_source', null);
    }
    
    /*
     * 查询
     */
    public function search($query, array $options = null)
    {
        if (is_array($query)) {
			$options['query'] = $query;
            $result = $this->call('POST', '_search', null, $options);
        } else {
			$options['q'] = $query;
			$result = $this->call('GET', '_search', $options);
        }
        if ($this->raw) {
            return $result;
        }
        return isset($result['hits']['hits']) ? array_column($result['hits']['hits'], '_source') : null;
    }
    
    /*
     * 设置
     */
    public function set($id, $data, $options = null)
    {
        return $this->result($this->call('PUT', $id, $options, $data), 'created');
    }

    /*
     * 创建
     */
    public function create($data, $options = null)
    {
        return $this->result($this->call('POST', null, $options, $data), 'created');
    }
    
    /*
     * 更新
     */
    public function update($query, $data, $options = null)
    {
		if (is_array($query)) {
			$data['query'] = $query;
			$result = $this->call('POST', '_update_by_query', $options, $data);
		} else {
			$result = $this->call('POST', "$query/_update", $options, $data);
		}
		return $this->result($result, 'updated');
    }
    
    /*
     * 删除
     */
    public function delete($query, $options = null)
    {
        if (!is_array($query)) {
            return $this->result($this->call('DELETE', $query, $options), 'found');
        }
        return $this->result($this->call('POST', '_delete_by_query', $options, ['query' => $query]), 'found');
    }
    
    /*
     * 设置是否返回原始结果
     */
    public function raw($bool = true)
    {
        $this->raw = $bool;
        return $this;
    }

    /*
     * 调用
     */
    public function call($method, $path = null, array $query = null, array $data = null)
    {
        $url = $this->endpoint;
        if ($path) {
            $url .= "/$path";
        }
        if ($query) {
            $url .= '?'.http_build_query($query);
        }
        $client = new Client($method, $url);
        if ($data) {
            $client->json($data);
        }
        $response = $client->response();
        if ($response->status >= 200 && $response->status < 300) {
            return $response->json();
        }
        error($client->error);
    }
    
    /*
     * 结果处理
     */
    protected function result($result, $key, $default = false)
    {
        return $this->raw ? $result : ($result[$key] ?? $default);
    }
}
