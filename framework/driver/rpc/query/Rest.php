<?php
namespace framework\driver\rpc\query;

class Rest extends Query
{
    public function with($class)
    {
        $this->ns[] = $class;
        return $this;
    }
}