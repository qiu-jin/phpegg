<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Jsonrpc;

class JsonrpcBatch
{
	// 请求id
    protected $id;
	// namespace
    protected $ns;
	// 配置项
    protected $config;
	// client实例
    protected $client;
	// 请求集合
    protected $queries;
	// 公共namespace
    protected $common_ns;
    
    /*
     * 构造函数
     */
    public function __construct($common_ns, $client, $config)
    {
        $this->client = $client;
        $this->config = $config;
        if ($common_ns) {
            $this->ns[] = $this->common_ns[] = $common_ns;
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
        if ($method === ($this->config['batch_call_method_alias'] ?? 'call')) {
            return $this->call(...$params);
        } elseif ($method === ($this->config['id_method_alias'] ?? 'id')) {
            $this->id = $params[0];
        } else {
            $this->ns[] = $method;
            $data = [
                'jsonrpc'   => Jsonrpc::VERSION,
                'method'    => implode('.', $this->ns),
                'params'    => $params,
            ];
			if (isset($this->id)) {
				$data['id'] = $this->id;
				$this->id = null;
			} elseif ($this->config['auto_unique_id']) {
				$data['id'] = uniqid();
			}
			$this->queries[] = $data;
            $this->ns = $this->common_ns;
        }
        return $this;
    }

    /*
     * 调用
     */
    protected function call($handler = null)
    {
        $result = $this->client->send($this->queries);
        if ($handler === null) {
            return $result;
        } elseif ($handler === true) {
            return array_map(function ($v) {
                return $v['result'] ?? (isset($v['result']) ? false : null);
            }, $result);
        } elseif (is_callable($handler)) {
            return array_map($handler, $result);
        }
        throw new \Exception('Invalid call handler type');
    }
}