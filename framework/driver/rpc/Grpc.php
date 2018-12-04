<?php
namespace framework\driver\rpc;

use framework\core\Loader;

class Grpc extends Rpc
{
    protected $client;
    // 默认配置
    protected $config = [
        /*
        // 服务主机（GRPC）
        'host'              => null,
        // 服务端口（GRPC）
        'port'              => null,
        // 服务端点（HTTP）
        'endpoint'          => null,
        // 公共headers（HTTP）
        'http_headers'      => null,
        // CURL设置（HTTP）
        'http_curlopts'     => null,
        // grpc设置（GRPC）
        'grpc_options'      => null,
        // service类名前缀
        'service_prefix'    => null,
        */
        /* 参数模式
         * 0 普通参数模式
         * 1 哈希数组参数模式
         * 2 原生message参数模式
         */
        'param_mode'                => 0,
        // response转化为数组
        'response_to_array'         => true,
        // 请求参数协议类格式
        'request_message_format'    => '{service}{method}Request',
        // 响应结构协议类格式
        'response_message_format'   => '{service}{method}Response',
    ];
    
    public function __construct($config)
    {
        $this->config = $config + $this->config;
        if (isset($this->config['endpoint'])) {
            $this->client = new client\GrpcHttp($this->config);
        } else {
            $this->client = new client\Grpc($this->config);
        }
        if (isset($this->config['service_load_rules'])) {
            foreach ($this->config['service_load_rules'] as $type => $rules) {
                Loader::add($type, $rules);
            }
        }
    }
    
    /*
     * query实例
     */
    public function query($name = null)
    {
        $ns = [];
        if (isset($this->config['service_prefix'])) {
            $ns[] = $this->config['service_prefix'];
        }
        if (isset($name)) {
            $ns[] = $name;
        }
        return new query\Grpc($ns, $this->client, $this->config);
    }
}