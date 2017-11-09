<?php
namespace framework\driver\rpc\query;

use framework\core\Loader;
use framework\core\http\Client;

/*
 * https://github.com/google/protobuf
 */
use Google\Protobuf\Internal\Message;

class GrpcSimple
{
    protected $ns;
    protected $rpc;
    protected $options;
    
    public function __construct($rpc, $ns, $options)
    {
        $this->ns = $ns;
        $this->rpc = $rpc;
        $this->options = $options;
    }
    
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        return $this->call($method, $params);
    }
    
    protected function call($method, $params)
    {
        if (!$this->ns) {
            throw new \Exception('NS is empty');
        }
        if (empty($this->options['auto_bind_param'])) {
            if (count($params) !== 1 || !is_subclass_of($params[0], Message::class)) {
                throw new \Exception('Invalid params');
            } 
            $param = $params[0];
        } else {
            $param = $this->buildParams(implode('\\', $this->ns), $method, $params);
        }
        $client = Client::post($this->options['endpoint'])->body($params->serializeToString());

        error("$status->code: $status->details");
    }
}