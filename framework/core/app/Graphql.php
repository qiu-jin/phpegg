<?php
namespace framework\core\app;

use framework\App;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

/*
 * https://github.com/webonyx/graphql-php
 * composer require webonyx/graphql-php
 */
use GraphQL\GraphQL;
use GraphQL\Type\Schema;

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
        if (Request::isPost()) {
            $request = Request::body()；
        }
        if ($request && ($result = $this->parseRequest($request))) {
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
    
    protected function respond($return)
    {
        
    }
    
    protected function parseRequest($request)
    {
        
    }
}
