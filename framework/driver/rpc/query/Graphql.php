<?php
namespace framework\driver\rpc\query;

class Graphql
{
	// 配置
	protected $config;
	// 缩进
	protected $indent;
	// 动作
	protected $action;
	// 字段
	protected $fields;
	// client实例
	protected $client;
	
    /*
     * 构造函数
     */
    public function __construct($config, $indent, $action = null, array $fields = [], $client = null)
    {
		$this->config = $config;
		$this->indent = $indent;
		$this->action = $action;
		$this->fields = $fields;
		$this->client = $client;
    }

    /*
     * 魔术方法
     */
    public function __call($method, $params)
    {
        switch ($m = strtolower($method)) {
            case $this->config['field_method_alias']:
                $this->fields = array_merge($this->fields, $params);
                return $this;
            case $this->config['exec_method_alias']:
                return $this->exec(...$params);
            default:
                return $this->exec($method, ...$params);
        }
    }
	
    /*
     * 执行
     */
    protected function exec($method = null, array $params = [])
    {
		$gql = $fields = '';
		if ($this->action) {
			$gql .= "$this->action ";
		}
		if ($method) {
			$gql .= $method;
			if ($params) {
				foreach ($params as $k => $v) {
					$new_params[] = "$k: ".json_encode($v);
				}
				$gql .= '('.implode(', ', $new_params).')';
			}
			$gql .= ' ';
		}
		$indent = $this->indent."\t";
		if ($this->fields) {
			foreach ($this->fields as $field) {
				if ($field instanceof \Closure) {
					$field = $field(new self($this->config, $indent));
				}
				$fields .= $indent.$field."\r\n";
			}
		}
		$gql .= "{\r\n$fields$this->indent}";
		return $this->client ? $this->client->exec($gql) : $gql;
    }
}