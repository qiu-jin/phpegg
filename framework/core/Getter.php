<?php
namespace framework\core;

trait Getter
{
    /*
     * 获取Provider实例
     */
    public function __get($name)
    {
		$v = Container::provider($name);
		if ($v && !empty($v[1])) {
			if ($v[0] === Container::T_SERVICE) {
				// SERVICE 名称空间链实例
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
						throw new \Exception("属性命名不允许以下划线开头: $this->_ns");
		            }
				};
            } elseif ($v[0] === Container::T_DRIVER && Container::checkAllowDriverGetterPropertyAccess($name)) {
				// DRIVER 名称空间链实例
				return $this->$name = new class($name) {
		            private $_n;
		            public function __construct($name) {
		                $this->_n = $name;
		            }
					// 魔术方法，获取空间链实例或容器实例
		            public function __get($name) {
						if ($name[0] != '_') {
							return $this->$name = Container::driver($this->_n, $name);
						}
		               	throw new \Exception("属性命名不允许以下划线开头: $name");
		            }
				};
            }
			return $this->$name = Container::make($name);
		}
        if (($n = Container::getGetterDriversName()) && isset($this->$n) && isset($this->$n[$name])) {
			return $this->$name = Container::makeCustomProvider($this->$n[$name]);
        }
		if ($instance = Container::makeGetterCommonProvider($name)) {
			return $this->$name = $instance;
		}
		
		Error::trigger("Undefined property: $$name");
		
		//throw new \Exception("Undefined property: $$name");
    }
}
