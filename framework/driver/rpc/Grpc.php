<?php
namespace framework\driver\rpc;

//use Grpc\;
//use Google\Protobuf;

class Grpc
{

    public function __construct($config)
    {

    }

    public function __get($class)
    {
        return new query\Query($this, $class);
    }

    public function __call($method, $params = [])
    {
        return $this->__send(null, $method, $params);
    }
    
    public function __send($ns, $method, $params = null, $id = null)
    {

    }
    
    protected function __error($code, $message)
    {
        if ($this->throw_exception) {
            throw new \Exception("Jsonrpc error $code: $message");
        } else {
            return error("$code: $message");
        }
    }
}