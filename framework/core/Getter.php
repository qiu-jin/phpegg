<?php
namespace framework\core;

defined('app\env\GETTER_PROVIDERS_NAME') || define('app\env\GETTER_PROVIDERS_NAME', 'providers');

trait Getter
{
    public function __get($name)
    {
        $gpn = \app\env\GETTER_PROVIDERS_NAME;
        if (isset($this->$gpn) && isset($this->$gpn[$name])) {
            $value = $this->$gpn[$name];
            if (is_string($value)) {
                return $this->$name = Container::make($value);
            } elseif (is_array($value)) {
                return $this->$name = new $value[0](...array_slice($value, 1));
            } elseif ($value instanceof \Closure) {
                return $this->$name = $value();
            }
        } else {
            if ($type = Container::getProviderType($name)) {
                if ($type === 'model') {
                    return $this->$name = $this->__makeModelNs($name, Container::getProviderValue('model', $name));
                } else {
                    return $this->$name = Container::{"make$type"}($name);
                }
            }
        }
        throw new \Exception('Undefined property: $'.$name);
    }
    
    private static function __makeModelNs($ns, $depth)
    {
        return new class($ns, $depth) {
            protected $_ns;
            protected $_depth;
            public function __construct($ns, $depth) {
                $this->_ns[] = $ns;
                $this->_depth = $depth - 1;
            }
            public function __get($name) {
                $this->_ns[] = $name;
                if ($name[0] === '_') {
                    throw new \Exception('Undefined property: $'.implode('->', $this->_ns));
                }
                if ($this->_depth > 0) {
                    return $this->$name = new self($this->_ns, $this->_depth);
                } else {
                    return $this->$name = Container::model(implode('.', $this->_ns));
                }
            }
        };
    }
}
