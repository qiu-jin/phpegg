<?php
namespace framework\driver\rpc;

use framework\util\Arr;
use framework\core\Loader;

class Grpc
{
	// client实例
    protected $client;
    // 配置项
    protected $config = [
        /*
        // 服务主机（GRPC）
        'host'
        // 服务端口（GRPC）
        'port'
        // 服务端点（HTTP）
        'endpoint'
        // 公共headers（HTTP）
        'http_headers'
        // CURL设置（HTTP）
        'http_curlopts'
        // HTTP 请求编码
        'http_request_encode'	=> ['gzip' => 'gzencode'],
        // HTTP 响应解码
        'http_response_decode'	=> ['gzip' => 'gzdecode'],
        // grpc设置（GRPC）
        'grpc_options'
        // service类名前缀
        'service_prefix'
        // schema定义文件加载规则
        'schema_loader_rules'
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
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config + $this->config;
        if (isset($this->config['endpoint'])) {
            $this->client = new client\GrpcHttp($this->config);
        } else {
            $this->client = new client\Grpc($this->config);
        }
        if (isset($this->config['schema_loader_rules'])) {
            foreach ($this->config['schema_loader_rules'] as $type => $rules) {
                Loader::add($type, $rules);
            }
        }
    }

    /*
     * 魔术方法，query实例
     */
    public function __get($name)
    {
        return $this->query($name);
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