<?php
namespace framework\core;

defined('app\env\GETTER_PROVIDERS_NAME') || define('app\env\GETTER_PROVIDERS_NAME', 'providers');

trait Getter
{
    public function __get($name)
    {
        $providers = \app\env\GETTER_PROVIDERS_NAME;
        if (isset($this->$providers) && isset($this->$providers[$name])) {
            $value = $this->$providers[$name];
            if (is_string($value)) {
                return $this->$name = Container::make($value);
            } elseif (is_array($value)) {
                $class = array_shift($value);
                return $this->$name = new $class(...$value);
            } elseif ($value instanceof \Closure) {
                return $this->$name = $value();
            }
        } elseif ($object = Container::make($name)) {
            return $this->$name = $object;
        }
        throw new \Exception('Undefined property: '.__CLASS__.'::$'.$name);
    }
}
