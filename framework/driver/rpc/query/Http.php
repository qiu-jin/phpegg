<?php
namespace framework\driver\rpc\query;

class Http extends Query
{
    public function __call($method, $params = [])
    {
        if ($method === 'with' || (isset($this->client_method_alias[$method]) && $this->client_method_alias[$method] === 'with') ) {
            $this->ns[] = $class;
            return $this;
        }
        return parent::__call($method, $params);
    }
}