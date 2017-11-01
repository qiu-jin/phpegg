<?php
namespace framework\driver\rpc;

class Rest extends Http
{
    const ALLOW_HTTP_METHODS = [
        'get', 'put', 'post', 'delete', 'patch'/*, 'option', 'head'*/
    ];
    
    public function query($name = null)
    {
        return new query\Http($this, $name);
    }
    
    public function batch($common_ns = null, $common_client_methods = null, $options = null)
    {
        return new query\HttpBatch($this, $common_ns, $common_client_methods, $options);
    }
}