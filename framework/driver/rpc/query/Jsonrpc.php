<?php
namespace framework\driver\rpc\query;

class Jsonrpc
{
	// 请求id
    protected $id;
	// namespace
    protected $ns;
	// client实例
    protected $client;
	// 参数模式
    protected $param_mode;
    
    /*
     * 构造函数
     */
    public function __construct($client, $param_mode, $name, $id)
    {
		$this->id = $id;
        $this->client = $client;
		$this->param_mode = $param_mode;
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
        return $this->call($this->param_mode ? ($params[0] ?? null) : $params);
    }
    
    /*
     * 调用
     */
    protected function call($params)
    {
        $data = ['jsonrpc' => '2.0', 'method' => implode('.', $this->ns), 'params' => $params];
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