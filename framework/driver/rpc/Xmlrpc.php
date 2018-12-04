<?php
namespace framework\driver\rpc;

class Xmlrpc extends Rpc
{
    // 默认配置
    protected $config = [
        /*
        // 服务端点
        'endpoint'          => null,
        // HTTP请求headers
        'http_headers'      => null,
        // HTTP请求curlopts
        'http_curlopts'     => null,
        */
    ];
    
    public function __construct($config)
    {
        $this->config = $config + $this->config;
    }
    
    /*
     * query实例
     */
    public function query($name = null)
    {
        return new query\Xmlrpc($name, $this->config);
    }
}