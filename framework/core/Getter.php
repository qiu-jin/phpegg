<?php
namespace framework\core;

trait Getter
{
    /*
     * 获取Provider实例
     */
    public function __get($name)
    {
        $n = Container::getGetterDriversName();
        if (isset($this->$n) && isset($this->$n[$name])) {
			return $this->$name = Container::makeCustomProvider($this->$n[$name]);
        } elseif ($v = Container::provider($name)) {
			if ($v[0] === Container::T_MODEL) {
				// 模型名称空间链实例
				return $this->$name = new class($name, $v[1] ?? 1) {
		            private $_ns;
		            private $_depth;
		            public function __construct($ns, $depth) {
		                $this->_ns = $ns;
		                $this->_depth = $depth - 1;
		            }
					// 魔术方法，获取空间链实例或容器实例
		            public function __get($name) {
		                $this->_ns .= ".$name";
		                if ($name[0] != '_') {
			                if ($this->_depth > 0) {
								return $this->$name = new self($this->_ns, $this->_depth);
			                } else {
								return $this->$name = Container::make($this->_ns);
			                }
		                }
						throw new \Exception("模型属性命名不允许以下划线开头: $this->_ns");
		            }
				};
            } elseif ($v[0] === Container::T_DRIVER && Container::checkGetterDriversArrayAccess($name)) {
				// 驱动名称空间链实例
				return $this->$name = new class($name) implements \ArrayAccess {
   		            private $type;
					private $instances;
   		            public function __construct($type) {
   		                $this->type = $type;
   		            }
				    public function offsetExists($key) {
						return isset($this->instances[$key]);
				    }
				    public function offsetGet($key) {
						return $this->instances[$key] ?? ($this->instances[$key] = Container::driver($this->type, $key));
				    }
				    public function offsetSet($key, $value) {
						$this->instances[$key] = $value;
				    }
				    public function offsetUnset($key) {
						if (isset($this->instances[$key])) {
							unset($this->instances[$key]);
						}
				    }
				};
			}
			return $this->$name = Container::make($name);
        }
		throw new \Exception("Undefined property: $$name");
    }
}
