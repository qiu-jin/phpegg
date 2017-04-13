<?php
namespace framework\driver\rpc;

use framework\core\Exception;
use framework\core\http\Client;
use framework\extend\rpc\Batch;
use framework\extend\rpc\Names;

class Jsonrpc
{
    private $server;
    private $throw_exception = true;
    
    public function __construct($config)
    {
        $this->server = $config['server'];
    }
    
    public function call($ns, $method, $params = null, $id = null)
    {
        $body = json_encode([
            'jsonrpc'   => '2.0',
            'method'    => $method,
            'params'    => $params,
            'id'        => empty($id) ? uniqid() : $id
        ]);
        $res = Client::send('POST', $this->server, [
            'body' => $body,
            'return_status' => true,
            'headers' => ['Content-Type: application/json; charset=UTF-8'] 
        ]);
        if ($res['status'] === 200 && !empty($res['body'])) {
            $data = json_decode($res['body'], true);
            if (isset($data['result'])) {
                return $data['result'];
            } elseif (isset($data['error'])) {
                $this->_error($data['error']['code'], $data['error']['message']);
                return false;
            }
        }
        $this->_error('-32603', 'nvalid JSON-RPC response');
        return false;
    }
    
    public function batch()
    {
        return new Batch($this);
    }

    public function __get($class)
    {
        return new Names($this, $class);
    }

    public function __call($method, $params = [])
    {
        return $this->call(null, $method, $params);
    }
    
    private function _send($data)
    {

    }
    
    private function _error($code, $message)
    {
        if ($this->throw_exception) {
            throw new Exception("$code: $message", Exception::RPC);
        } else {
            $this->log = "$code: $message";
        }
    }
}