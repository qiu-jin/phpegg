<?php
namespace framework\driver\rpc;

class Httprpc extends Http
{
    /*
     * query实例
     */
    public function __get($name)
    {
        return $this->query($name);
    }
    /*
     * query实例
     */
    public function query($name = null, $filters = null)
    {
        return new query\Httprpc($this->client, $name, $filters);
    }
}