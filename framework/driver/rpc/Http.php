<?php
namespace framework\driver\rpc;

class Http
{
    protected $client;
    
    protected $config = [
        /*
        // 服务端点
        'endpoint'              => null,
        // URL后缀名
        'url_suffix'            => null,
        // URL风格转换
        'url_style'             => null,
        // 请求公共headers
        'http_headers'          => null,
        // 请求公共curlopts
        'http_curlopts'         => null,
        //
        'ns_method_alias'       => 'ns',
        //
        'filter_method_alias'   => 'filter',
        //
        'build_method_alias'    => 'build',
        //
        'then_method_alias'     => 'then',
        //
        'batch_call_method_alias'   => 'call',
        //
        'batch_select_timeout'  => 0.1,
        // 请求内容编码
        'requset_encode'        => null,
        // 响应内容解码
        'response_decode'       => null,
        // 响应结果字段
        'response_result_field' => null,
        // 
        'response_ignore_error' => null,
        // 
        'error_code_field'      => null,
        //
        'error_message_field'   => null,
        */
    ];

    public function __construct($config)
    {
        $this->config = $config + $this->config;
        $this->client = new client\Http($this->config);
    }
    
    public function __get($name)
    {
        return $this->query($name);
    }
    
    public function __call($method, $params)
    {
        return $this->query()->$method(...$params);
    }
    
    public function query($name = null)
    {
        return new query\Http($this->client, $name, $this->config);
    }
    
    public function batch($common_ns = null, callable $common_build_handler = null)
    {
        return new query\HttpBatch($this->client, $common_ns, $this->config, $common_build_handler);
    }
}