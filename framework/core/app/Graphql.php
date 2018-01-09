<?php
namespace framework\core\app;

use framework\App;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

class Graphql extends App
{
    protected $config = [
        'schema_ns'         => 'schema',
        'check_field_type'  => false,
        'max_resolve'       => 5
    ];
    // 返回值
    protected $return;
    
    protected function dispatch()
    {
        if (($body = Request::body()) && ($result = $this->parseBody($body))) {
            return $result;
        }
        return false;
    }
    
    protected function call()
    {
        
    }
    
    protected function error($code = null, $message = null)
    {
        
    }
    
    protected function response($return)
    {
        
    }
    
    protected function parseBody($body)
    {
        
    }
}
