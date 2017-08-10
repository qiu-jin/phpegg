<?php
namespace framework\core;

trait Getter
{
    protected $providers;
    
    public function __get($name)
    {
        if (isset($this->providers[$name])) {
            $value = $this->providers[$name];
            if (is_string($value)) {
                return $this->$name = Container::get($value);
            } elseif (is_array($value)) {
                $class = array_shift($value);
                return $this->$name = new $class(...$value);
            } elseif (is_callable($value)) {
                return $this->$name = $value();
            }
        } elseif ($object = Container::get($name)) {
            return $this->$name = $object;
        }
        throw new \Exception('Undefined property: '.__CLASS__.'::$'.$name);
    }
}
