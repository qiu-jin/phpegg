<?php
namespace framework\driver\rpc;

class Rest extends Http
{
    // 允许的HTTP请求方法
    const ALLOW_HTTP_METHODS = [
        'get', 'put', 'post', 'delete', 'patch', 'options', 'head'
    ];
    
    /*
     * query实例
     */
    public function query($name = null)
    {
        return new query\Rest($this->client, $name);
    }
    
    /*
     * 批请求
     */
    public function batch($common_ns = null, callable $common_build_handler = null)
    {
        return new query\RestBatch($this->client, $common_ns, $this->config, $common_build_handler);
    }
}