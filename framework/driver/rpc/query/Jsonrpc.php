<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Jsonrpc as Jrpc;

class Jsonrpc
{
	// 请求id
    protected $id;
	// namespace
    protected $ns;
	// client实例
    protected $client;
	// 配置项
    protected $config;
    
    /*
     * 构造函数
     */
    public function __construct($name, $client, $config)
    {
        $this->client = $client;
        if (isset($name)) {
            $this->ns[] = $name;
        }
        $this->config = $config;
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
        if ($method === $this->config['id_method_alias'])) {
            $this->id = $params[0];
            return $this;
        }
        $this->ns[] = $method;
        return $this->call($params);
    }
    
    /*
     * 调用
     */
    protected function call($params)
    {
        $data = [
            'jsonrpc'   => Jrpc::VERSION,
            'method'    => implode('.', $this->ns),
            'params'    => $params,
        ];
		if (isset($this->id)) {
			$data['id'] = $this->id;
		} elseif ($this->config['id_generator']) {
			$data['id'] = $this->config['id_generator']();
		}
        $result = $this->client->send($data);
		if (isset($data['id'])) {
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