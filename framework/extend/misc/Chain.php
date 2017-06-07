<?php
namespace framework\util;

class Chain
{
    protected $ns;
    protected $object;
    protected $option;
    protected $call_methods;
    protected $chain_methods;
    
    public function __construct(object $object, $name, array $chain_methods = null, array $call_methods = null)
    {
        $this->ns[] = $name;
        $this->object = $object;
        $this->call_methods = $call_methods;
        $this->chain_methods = $chain_methods;
    }
    
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($name, $params = [])
    {
        if ($this->chain_methods && in_array($name, $this->chain_methods, true)) {
            $this->option[$name] = $params;
            return $this;
        }
        $this->ns[] = $name;
        if ($this->call_methods) {
            if (in_array($name, $this->call_methods, true)) {
                $this->object->call_methods[$name]($this->ns, $this->option, ...$params);
            }
        } else {
            $this->object->call($this->ns, $this->option, ...$params);
        }
    }
}
