<?php
namespace framework\driver\rpc;

class Rest extends Http
{
    const ALLOW_HTTP_METHODS = [
        'get', 'put', 'post', 'delete', 'patch'/*, 'option', 'head'*/
    ];
    
    public function query($name, $client_methods = null)
    {
        return new query\Rest($this, $name, $client_methods);
    }
}