<?php
namespace framework\driver\search\query;

use framework\core\http\Client;

class ElasticBatch
{
    protected $ns;
    protected $url;
    protected $queries;
    protected $default_type;
    protected static $allow_methods = [
        'get', 'put', 'index', 'update', 'delete'
    ];
    
    public function __construct($url, $default_type)
    {
        $this->url = $url;
        $this->default_type = $default_type;
    }
    
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if (in_array($method, self::$allow_methods, true)) {
            $this->ns = null;
            $this->queries[$ns][] = [$method, $params];
            return $this;
        }
        throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
    }
    
    protected function call()
    {
        if (!$this->queries) {
            throw new \Exception('No query');
        }
    }
}
