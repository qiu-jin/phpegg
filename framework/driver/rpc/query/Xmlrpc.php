<?php
namespace framework\driver\rpc\query;

use framework\App;
use framework\core\http\Client;

class Xmlrpc
{
	// namespace
    protected $ns;
	// 配置项
    protected $config;
    
    /*
     * 构造函数
     */
    public function __construct($name, $config)
    {
        if (isset($name)) {
            $this->ns[] = $name;
        }
        $this->config = $config;
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
        $this->ns[] = $method;
        return $this->call($params);
    }
    
    /*
     * 调用
     */
    protected function call($params)
    {
        $client = Client::post($this->config['endpoint']);
        $client->header('User-Agent', 'phpegg'.App::VERSION);
        $client->header('Content-Type', 'text/xml');
        if (isset($this->config['http_headers'])) {
            $client->headers($this->config['http_headers']);
        }
        if (isset($this->config['http_curlopts'])) {
            $client->curlopts($this->config['http_curlopts']);
        }
        $client->body(xmlrpc_encode_request(implode('.', $this->ns), $params));
        if (($result = $client->response()->body) !== false) {
            if ($result = xmlrpc_decode($result)) {
                if (!xmlrpc_is_fault($result)) {
                    return $result;
                } elseif (isset($result['faultCode'])) {
                    error($result['faultCode'].': '.$result['faultString']);
                }
            }
        }
        error('-32000: Invalid response');
    }
}