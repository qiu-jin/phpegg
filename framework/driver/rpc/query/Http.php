<?php
namespace framework\driver\rpc\query;

class Http extends Query
{
    public function with($class)
    {
        $this->ns[] = $class;
        return $this;
    }
}