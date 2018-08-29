<?php
namespace framework\driver\rpc\query;

/*
 * https://github.com/google/protobuf
 */
use Google\Protobuf\Internal\Message;

class Grpc
{
    protected $ns;
    protected $client;
    protected $config;

    public function __construct($ns, $client, $config)
    {
        $this->ns = $ns;
        $this->client = $client;
        $this->config = $config;
    }
    
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if (!$this->ns) {
            throw new \Exception('Service is empty');
        }
        $service = implode('\\', $this->ns);
        $message = $this->makeRequestMessage($service, $method, $params);
        return $this->response($this->client->send($service, $method, $message));
    }
    
    protected function response($message)
    {
        if (empty($this->config['response_to_array'])) {
            return $message;
        }
        return json_decode($message->serializeToJsonString(), true);
    }
    
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