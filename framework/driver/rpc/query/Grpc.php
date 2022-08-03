<?php
namespace framework\driver\rpc\query;

/*
 * https://github.com/google/protobuf
 */
use Google\Protobuf\Internal\Message;

class Grpc
{
	// namespace
    protected $ns;
	// client实例
    protected $client;
	// 配置项
    protected $config;

    /*
     * 构造函数
     */
    public function __construct($ns, $client, $config)
    {
        $this->ns = $ns;
        $this->client = $client;
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
        if (!$this->ns) {
            throw new \Exception('Service is empty');
        }
        $service = implode('\\', $this->ns);
        $message = $this->makeRequestMessage($service, $method, $params);
        return $this->response($this->client->send($service, $method, $message));
    }
    
    /*
     * 响应信息处理
     */
    protected function response($message)
    {
        if (empty($this->config['response_to_array'])) {
            return $message;
        }
        return json_decode($message->serializeToJsonString(), true);
    }
    
    /*
     * 生成请求信息实例
     */
    protected function makeRequestMessage($service, $method, $params)
    {
        switch ($this->config['param_mode']) {
            case '1':
                if (!is_array($params[0])) {
                    throw new \Exception('Invalid params');
                }
                $class = strtr($this->config['request_message_format'], [
                    '{service}' => $service,
                    '{method}'  => ucfirst($method)
                ]);
                $message = new $class;
                $message->mergeFromJsonString(json_encode($return));
                return $message;
            case '2':
                if (is_subclass_of($params[0], Message::class)) {
                    return $params[0];
                }
                throw new \Exception('Invalid params');
            default:
                $class = strtr($this->config['request_message_format'], [
                    '{service}' => $service,
                    '{method}'  => ucfirst($method)
                ]);
                return \Closure::bind(function ($params) {
                    foreach (array_keys(get_class_vars(get_class($this))) as $i => $k) {
                        if (isset($params[$i])) {
                            $this->$k = $params[$i];
                        }
                    }
                    return $this;
                }, new $class, $class)($params);
        }
    }
}