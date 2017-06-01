<?php
namespace framework\driver\rpc;

use framework\core\Error;
use framework\core\http\Client;

class Jsonrpc
{
    protected $server;
    protected $throw_exception = false;
    
    public function __construct($config)
    {
        $this->server = $config['server'];
        if (isset($config['throw_exception'])) {
            $this->throw_exception = (bool) $config['throw_exception'];
        }
    }

    public function __get($class)
    {
        return new query\Names($this, $class);
    }

    public function __call($method, $params = [])
    {
        return $this->call(null, $method, $params);
    }
    
    public function call($ns, $method, $params = null, $id = null)
    {
        $ns[] = $method;
        $data = [
            'jsonrpc'   => '2.0',
            'params'    => $params,
            'method'    => implode('.', $ns),
            'id'        => empty($id) ? uniqid() : $id
        ];
        $client = Client::post($this->server)->json($data);
        $data = $client->getJson();
        if (isset($data['result'])) {
            return $data['result'];
        }
        if (isset($data['error'])) {
            $this->error($data['error']['code'], $data['error']['message']);
        } else {
            $clierr = $client->error;
            if ($clierr) {
                $this->error('-32000', "Internet error $clierr[0]: $clierr[1]");
            } else {
                $this->error('-32603', 'nvalid JSON-RPC response');
            }
        }
        return false;
    }
    
    protected function error($code, $message)
    {
        if ($this->throw_exception) {
            throw new \Exception("Jsonrpc error $code: $message");
        } else {
            return error("$code: $message");
        }
    }
}