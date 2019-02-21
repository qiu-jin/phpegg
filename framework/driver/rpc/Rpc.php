<?php
namespace framework\driver\rpc;

abstract class Rpc
{
    /*
     * 魔术方法，query实例
     */
    public function __get($name)
    {
        return $this->query($name);
    }

    /*
     * 魔术方法，直接调用
     */
    public function __call($method, $params)
    {
        return $this->query()->$method(...$params);
    }
    
    /* 
     * query实例
     */
    abstract public function query($name);
}