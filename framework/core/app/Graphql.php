<?php
namespace framework\core\app;

use framework\App;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

class Graphql extends App
{
    protected $config = [
        
    ];
    // 返回值
    protected $return;
    
    protected function dispatch()
    {
        if ($body = Request::body() && $query = $this->parseBody($body)) {
            return compact('query');
        }
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
