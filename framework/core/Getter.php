<?php
namespace framework\core;

trait Getter
{
    protected $container;
    
    public function __get($name)
    {
        if (isset($this->container[$name])) {
            $value = $this->container[$name];
            if (is_string($value)) {
                return $this->$name = Container::get($value);
            } elseif (is_array($value)) {
                $class = array_shift($value);
                return $this->$name = new $class(...$value);
            }
        } else {
            return $this->$name = Container::get($name);
        }
        throw new \Exception('Undefined property: '.__CLASS__.'::$'.$name);
    }
}
