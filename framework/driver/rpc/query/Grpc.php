<?php
namespace framework\driver\rpc\query;

/*
 * https://github.com/google/protobuf
 */

class Grpc
{
	// namespace
    protected $ns;
	// client实例
    protected $client;

    /*
     * 构造函数
     */
    public function __construct($client, $name)
    {
        $this->client = $client;
        if (isset($name)) {
            $this->ns[] = $name;
        }
    }
    
    /*
     * 魔术方法，设置namespace
     */
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    /*
     * 魔术方法，调用rpc方法
     */
    public function __call($method, $params)
    {
		$response_message = $this->client->send(implode('\\', $this->ns), $method, ...$params);
		return empty($params[1]) ? $response_message ? json_decode($response_message->serializeToJsonString(), true);
    }
}