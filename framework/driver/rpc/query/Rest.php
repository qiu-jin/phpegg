<?php
namespace framework\driver\rpc\query;

class Rest
{
	// namespace
    protected $ns;
	// client实例
    protected $client;
	// http method
    protected $method;
	// 配置
	protected $config = [
		// 标准方法
		'standard_methods' => [
			'get'	 => 'GET',
			'list'   => 'GET',
			'create' => 'POST',
			'update' => 'POST',
			'delete' => 'DELETE',
		],
		// 自定义方法前缀符
		'custom_method_prefix' => '/',
	];
	// filter设置
    protected $filters;
	// headers设置
    protected $headers;
	
    /*
     * 构造函数
     */
    public function __construct($client, $config, $path, $filters, $headers, $method)
    {
        $this->ns = explode('/', $path);
        $this->client = $client;
		$this->config = array_intersect_key($this->config, $config) + $this->config;
		$this->filters = $filters;
		$this->headers = $headers;
        if ($method) {
			$m = strtoupper($method);
			if (in_array($m, ['GET', 'POST', 'DELETE', 'PUT', 'PATCH', 'OPTIONS'])) {
				$this->method = $m;
		    } else {
		    	throw new \Exception('Call to undefined method '.__CLASS__."::$method");
		    }
        }
    }

    /*
     * 魔术方法，调用rpc方法
     */
    public function __call($name, $params)
    {
		$data = null;
		if ($params) {
			$i = count($params) - 1;
			if (is_array($params[$i]) || is_object($params[$i])) {
				$data = array_pop($params);
			}
		}
		foreach ($this->ns as $i => $v) {
			if ($v === '*') {
				if ($params) {
					$this->ns[$i] = array_shift($params);
				} else {
					throw new \Exception('Resource error:'.implode('/', $this->ns));
				}
			}
		}
		$n = strtolower($name);
		if (isset($this->config['standard_methods'][$n])) {
			return $this->call($this->config['standard_methods'][$n], $data);
		} else {
			return $this->call(isset($data) ? 'POST' : 'GET', $data, $name);
		}
    }
    
    /*
     * 调用
     */
    protected function call($method, $data = null, $custom_method = null)
    {
		$path = implode('/', $this->ns);
		if ($custom_method) {
			$path .= $this->config['custom_method_prefix'].$custom_method;
		}
		return $this->client->send($this->method ?? $method, $path, $this->filters, $data, $this->headers);
    }
}