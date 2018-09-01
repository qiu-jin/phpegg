<?php
namespace framework\driver\rpc;

class Rest extends Http
{
    const ALLOW_HTTP_METHODS = [
        'get', 'put', 'post', 'delete', 'patch', 'options', 'head'
    ];
    
    public function query($name = null)
    {
        return new query\Rest($this->client, $name);
    }
    
    public function batch($common_ns = null, callable $common_build_handler = null)
    {
        return new query\RestBatch($this->client, $common_ns, $this->config, $common_build_handler);
    }
}