<?php
namespace framework\driver\rpc;

class Http extends Rpc
{
	// client实例
    protected $client;
    // 配置项
    protected $config = [
        /*
        // 服务端点
        'endpoint'
        // URL后缀名
        'url_suffix'
        // URL路径转换类型
		// 下划线转中划线 snake_to_spinal
		// 驼峰转中划线 camel_to_spinal
		// 下划线转驼峰 snake_to_camel
		// 驼峰转下划线 camel_to_snake
        'convert_path_style'
        // 请求公共headers
        'http_headers'
        // 请求公共curlopts
        'http_curlopts'
        // 请求内容编码
        'requset_encode'
        // 响应内容解码
        'response_decode'
        // 响应结果字段
        'response_result_field'
        // 忽略错误返回false
        'response_ignore_error'
        // 错误码定义字段
        'error_code_field'
        // 错误信息定义字段
        'error_message_field'
        // ns方法别名
        'ns_method_alias'       => 'ns',
        // filter方法别名
        'filter_method_alias'   => 'filter',
        // build方法别名
        'build_method_alias'    => 'build',
        // then方法别名
        'then_method_alias'     => 'then',
        // 批请求call方法别名
        'batch_call_method_alias' => 'call',
        // 批请求select超时
        'batch_select_timeout'    => 0.1,
        */
    ];

    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config + $this->config;
        $this->client = new client\Http($this->config);
    }
    
    /*
     * query实例
     */
    public function query($name = null)
    {
        return new query\Http($this->client, $name, $this->config);
    }
    
    /*
     * 批请求
     */
    public function batch($common_ns = null, callable $common_build_handler = null)
    {
        return new query\HttpBatch($this->client, $common_ns, $this->config, $common_build_handler);
    }
}