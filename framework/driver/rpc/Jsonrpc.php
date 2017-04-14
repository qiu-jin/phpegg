<?php
namespace framework\driver\rpc;

use framework\core\http\Client;
use framework\extend\rpc\Names;

class Jsonrpc
{
    protected $server;
    protected $throw_exception = false;
    
    public function __construct($config)
    {
        $this->server = $config['server'];
        if (isset($config['throw_exception'])) {
            $throw_exception = (bool) $config['throw_exception'];
        }
    }

    public function __get($class)
    {
        return new Names($this, $class);
    }

    public function __call($method, $params = [])
    {
        return $this->_call(null, $method, $params);
    }
    
    public function _call($ns, $method, $params = null, $id = null)
    {
        $data = [
            'jsonrpc'   => '2.0',
            'params'    => $params,
            'method'    => $ns ? implode('.', $ns).'.'.$method : $method,
            'id'        => empty($id) ? uniqid() : $id
        ];
        $client = Client::post($this->server)->json($data);
        $result = $client->json;
        if (isset($result['result'])) {
            return $result['result'];
        }
        if (isset($data['error'])) {
            $this->_error($data['error']['code'], $data['error']['message']);
        } else {
            if ($result->status == 200) {
                $this->_error('-32603', 'nvalid JSON-RPC response');
            }
            if ($clierr = $client->error) {
                $this->_error('-32000', "Internet error $clierr[0]: $clierr[1]");
            }
            $this->_error('-32000', 'Unknown internet error');
        }
        return false;
    }
    
    /*
    public function batch()
    {
        return new Batch($this);
    }
    
    public function _batchCall()
    {
        
    }
    */
    
    protected function _error($code, $message)
    {
        if ($this->throw_exception) {
            throw new \Exception("Jsonrpc error $code: $message");
        } else {
            $this->log = "$code: $message";
        }
    }
}