<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Jsonrpc;

class JsonrpcBatch
{
    protected $id;
    protected $ns;
    protected $config;
    protected $client;
    protected $queries;
    protected $common_ns;
    
    
    public function __construct($common_ns, $client, $config)
    {
        $this->client = $client;
        $this->config = $config;
        if ($common_ns) {
            $this->ns[] = $this->common_ns[] = $common_ns;
        }
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if ($method === ($this->config['batch_call_method_alias'] ?? 'call')) {
            return $this->call(...$params);
        } elseif ($method === ($this->config['id_method_alias'] ?? 'id')) {
            $this->id = $params[0];
        } else {
            $this->ns[] = $method;
            $this->queries[] = [
                'jsonrpc'   => Jsonrpc::VERSION,
                'method'    => implode('.', $this->ns),
                'params'    => $params,
                'id'        => $this->id ?? count($this->queries)
            ];
            $this->id = null;
            $this->ns = $this->common_ns;
        }
        return $this;
    }

    protected function call($handler = null)
    {
        $result = $this->client->send($this->queries);
        if ($handler === null) {
            return $result;
        } elseif ($handler === true) {
            return array_map(function ($v) {
                return $v['result'] ?? (isset($v['result']) ? false : null);
            }, $result);
        } elseif (is_callable($handler)) {
            return array_map($handler, $result);
        }
        throw new \Exception('Invalid call handler type');
    }
}