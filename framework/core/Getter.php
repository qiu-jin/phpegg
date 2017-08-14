<?php
namespace framework\core;

trait Getter
{
    protected $bind;
    
    public function __get($name)
    {
        if (isset($this->bind[$name])) {
            $value = $this->bind[$name];
            if (is_string($value)) {
                return $this->$name = Container::make($value);
            } elseif (is_array($value)) {
                $class = array_shift($value);
                return $this->$name = new $class(...$value);
            } elseif (is_callable($value)) {
                return $this->$name = $value();
            }
        } elseif ($object = Container::make($name)) {
            return $this->$name = $object;
        }
        throw new \Exception('Undefined property: '.__CLASS__.'::$'.$name);
    }
}
