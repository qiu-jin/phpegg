<?php
namespace framework\core;

trait Getter
{
    protected $connections;
    
    public function __get($name)
    {
        if (isset($this->connections[$name])) {
            $config = $this->connections[$name];
            $type = isset($config['type']) ? $config['type'] : $name;
            return $this->$name = Container::load($type, $config);
        } else {
            return $this->$name = Container::load($name);
        }
    }
}
