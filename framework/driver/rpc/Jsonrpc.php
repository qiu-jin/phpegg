<?php
namespace framework\driver\rpc;

use framework\core\http\Client;

class Jsonrpc
{
    const ALLOW_CLIENT_METHODS = ['header', 'timeout', 'debug'];
    
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function __get($class)
    {
        return new query\Query($this, $class);
    }

    public function __call($method, $params = [])
    {
        return $this->__send(null, $method, $params);
    }
    
    public function __send($ns, $method, $params, $client_methods = null)
    {
        $data = [
            'jsonrpc'   => '2.0',
            'params'    => $params,
            'method'    => implode('.', $ns).'.'.$method,
            'id'        => empty($id) ? uniqid() : $id
        ];
        $client = Client::post($this->host)->json($data);
        $data = $client->json;
        if (isset($data['result'])) {
            return $data['result'];
        }
        if (isset($data['error'])) {
            error($data['error']['code'].' '.$data['error']['message']);
        } else {
            $clierr = $client->error;
            if ($clierr) {
                error("-32000 Internet error $clierr[0]: $clierr[1]");
            } else {
                error('-32603 nvalid JSON-RPC response');
            }
        }
        return false;
    }
}