<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Rest as Restrpc;

class Rest
{
	// namespace
    protected $ns;
	// client实例
    protected $client;
	// filter设置
    protected $filters;
    
    /*
     * 构造函数
     */
    public function __construct($client, $name, $filters)
    {
        if (isset($name)) {
            $this->ns[] = $name;
        }
        $this->client = $client;
		$this->filters = $filters;
    }

    /*
     * 魔术方法，设置namespace
     */
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }

    /*
     * get方法
     */
    public function get()
    {
		$this->ns[] = $id;
		return $this->call('GET');
    }
	
    /*
     * list方法
     */
    public function list()
    {
		return $this->call('GET');
    }
	
    /*
     * create方法
     */
    public function create($data)
    {
		return $this->call('POST', null, $data);
    }
	
    /*
     * update方法
     */
    public function update($id, $data)
    {
		$this->ns[] = $id;
		return $this->call('PATCH', null, $data);
    }

    /*
     * delete方法
     */
    public function delete($id)
    {
		$this->ns[] = $id;
		return $this->call('DELETE');
    }
    
    /*
     * 魔术方法，调用rpc方法
     */
    public function __call($method, $params)
    {
		return $this->call('POST', $method, ...$params);
    }
    
    /*
     * 调用
     */
    protected function call($method, $custom_method = null, $data = null, $headers = null)
    {
		return $this->client->make($method, implode('/', $this->ns), $this->filters, $data, $headers);
    }
}