<?php
namespace framework\driver\rpc;

class Http
{
	// client实例
    protected $client;
    // 配置项
    protected $config = [
        /*
        // 服务端点
        'endpoint'
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
        // 抛出响应错误异常
        'throw_response_error'
        // 错误码定义字段
        'response_error_code_field'
        // 错误信息定义字段
        'response_error_message_field'
		*/
    ];

    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->client = new client\Http($this->config);
    }
    
    /*
     * query实例
     */
    public function query($name = null, $filters = null)
    {
        return new query\Http($this->client, $name, $filters);
    }
}