<?php
namespace framework\driver\rpc;

class Xmlrpc
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
        return new query\Xmlrpc($name, $this->config);
    }
}