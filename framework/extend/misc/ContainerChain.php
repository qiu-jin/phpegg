<?php
namespace framework\extend\misc;

class ContainerChain 
{
    private $ns;
    private $type;
    
    public function __construct($type)
    {
        $this->type = $type;
    }
    
    public function __get($class)
    {
        $this->ns[] = $class;
        return $this;
    }
    
    public function __call($method, $params = [])
    {
        if ($this->ns) {
            return Container::get(implode('.', $this->ns), $type)->$method(...$params);
        }
    }
}
