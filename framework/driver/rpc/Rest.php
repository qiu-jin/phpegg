<?php
namespace framework\driver\rpc;

class Rest extends Http
{    
    /*
     * query实例
     */
    public function query($name = null, $filters = null)
    {
        return new query\Rest($this->client, $name, $filters);
    }
}