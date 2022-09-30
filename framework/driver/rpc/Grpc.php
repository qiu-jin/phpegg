<?php
namespace framework\driver\rpc;

use framework\util\Arr;
use framework\core\Loader;

class Grpc
{
	// client实例
    protected $client;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        if (isset($config['endpoint'])) {
            $this->client = new client\GrpcHttp($config);
        } else {
            $this->client = new client\Grpc($config);
        }
        if (isset($config['schema_loader_rules'])) {
            foreach ($config['schema_loader_rules'] as $type => $rules) {
                Loader::add($type, $rules);
            }
        }
    }

    /*
     * 魔术方法，query实例
     */
    public function __get($name)
    {
        return $this->query($name);
    }
    
    /*
     * query实例
     */
    public function query($name = null)
    {
        return new query\Grpc($this->client, $name);
    }
}