<?php
namespace framework\driver\rpc\query;

class JsonrpcBatch
{
    protected $id;
    protected $ns;
    protected $rpc;
    protected $queries;
    protected $options = [
        'id_method_alias' => 'id',
        'call_method_alias'     => 'call',
    ];
    protected $common_ns;
    protected $common_client_methods;
    
    public function __construct($rpc, $common_ns, $common_client_methods, $options)
    {
        $this->rpc = $rpc;
        if ($common_ns) {
            $this->ns[] = $this->common_ns[] = $common_ns;
        }
        $this->common_client_methods = $common_client_methods;
        if (isset($options)) {
            $this->options = $options + $this->options;
        }
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if ($method === $this->options['call_method_alias']) {
            return $this->call(...$params);
        } elseif ($method === $this->options['id_method_alias']) {
            $this->id = $params[0];
        } else {
            $this->ns[] = $method;
            $this->queries[] = [
                'jsonrpc'   => $this->rpc::VERSION,
                'method'    => implode('.', $this->ns),
                'params'    => $params,
                'id'        => $this->id ?? count($this->queries)
            ];
            $this->id = null;
            $this->ns = $this->common_ns;
        }
        return $this;
    }

    protected function call(callable $handle = null)
    {
        $result = $this->rpc->getResult($this->queries, $this->common_client_methods);
        if (!isset($handle)) {
            return $result;
        }
        foreach ($result as $item) {
            $return[] = $handle($item);
        }
        return $return;
    }
}