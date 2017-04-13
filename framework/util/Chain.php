<?php
namespace framework\util;

class Chain
{
    protected $object;
    protected $option;
    protected $call_methods;
    protected $chain_methods;
    
    public function __construct(object $object, array $call_methods, array $chain_methods = null)
    {
        $this->object = $object;
        $this->call_methods = $call_methods;
        $this->chain_methods = $chain_methods;
    }
    
    public function __call($name, $params = [])
    {
        if (in_array($name, $this->chain_methods, true)) {
            $this->option[$name][] = $params;
            return $this;
        }
        if (in_array($name, $this->call_methods, true)) {
            return call_user_func([$this->object, $this->call_methods[$name]], $this->option, ...$params);
        }
    }
}
