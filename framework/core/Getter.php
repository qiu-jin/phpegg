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
        }
        $ctype = Container::getConnectionType($name);
        if ($ctype !== null) {
            return $this->$name = $ctype ? Container::load($name) : Container::make($name);
        }
        $mtype = Container::getModelType($name);
        if ($mtype !== null) {
            return $this->$name = new ModelGetter($name, $mtype);
        }
        throw new \Exception('Attr not exists: '.$name);
    }
}

class ModelGetter
{
    protected $__depth;
    protected $__prefix;

    public function __construct($prefix, $depth)
    {
        $this->__depth = $depth - 1;
        $this->__prefix = $prefix;
    }
    
    public function __get($name)
    {
        $class = $this->__prefix.'.'.$name;
        if ($this->__depth === 0) {
            return $this->$name = Container::get($class);
        }
        return $this->$name = new self($class, $this->__depth);
    }
    
    public function __call($method, $param = [])
    {
        return Container::get($this->__prefix)->$method(...$param);
    }
}
