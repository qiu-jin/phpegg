<?php
namespace framework\driver\rpc\query;

class Rest extends Query
{
    public function ns($name)
    {
        $this->ns[] = $name;
        return $this;
    }
}