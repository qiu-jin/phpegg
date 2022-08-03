<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Jsonrpc as JRPC;

class Jsonrpc
{
	// 请求id
    protected $id;
	// namespace
    protected $ns;
	// client实例
    protected $client;
    
    /*
     * 构造函数
     */
    public function __construct($name, $id, $client)
    {
		$this->id = $id;
        $this->client = $client;
        if (isset($name)) {
            $this->ns[] = $name;
        }
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
     * 魔术方法，调用rpc方法
     */
    public function __call($method, $params)
    {
        $this->ns[] = $method;
        return $this->call($params);
    }
    
    /*
     * 调用
     */
    protected function call($params)
    {
        $data = [
            'jsonrpc'   => JRPC::VERSION,
            'method'    => implode('.', $this->ns),
            'params'    => $params,
        ];
		if ($this->id === true) {
			$data['id'] = uniqid();
		} elseif ($this->id !== false) {
			$data['id'] = $this->id;
		}
        $result = $this->client->send($data);
		if (array_key_exists('id', $data)) {
	        if (isset($result['result'])) {
	            return $result['result'];
	        } elseif (isset($result['error'])) {
	            if (is_array($result['error'])) {
	                error($result['error']['code'].': '.$result['error']['message']);
	            } else {
	                error('-32000: '.$result['error']);
	            }
	        }
	        error('-32000: Invalid response');
		}
    }
}