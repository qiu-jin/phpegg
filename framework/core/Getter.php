<?php
namespace framework\core;

trait Getter
{
    /*
     * 获取实例
     */
    public function __get($name)
    {
		$v = Container::getProvider($name);
		if ($v && !empty($v[1])) {
			if ($v[0] === Container::T_DRIVER) {
				if ($v[1] === 2) {
					// DRIVER 属性访问驱动实例
					return $this->$name = new class($name) {
			            private $_n;
			            public function __construct($name) {
			                $this->_n = $name;
			            }
						// 魔术方法，获取驱动实例
			            public function __get($name) {
							if ($name[0] != '_') {
								return $this->$name = Container::driver($this->_n, $name);
							}
			               	throw new \Exception("属性命名不允许以下划线开头: $name");
			            }
					};
				}
				return $this->$name = Container::driver($name);
			} elseif ($v[0] === Container::T_MODEL) {
				/*
				return $this->$name = new class($name) {
		            private $_n;
		            public function __construct($name) {
		                $this->_n = $name;
		            }
					//
		            public function __call($method, $params) {
						if ($method[0] != '_') {
							$this->$method = Container::model($this->_n, $method);
							($this->$method)(...$params);
							return $this->$method
						}
		               	throw new \Exception("方法命名不允许以下划线开头: $method");
		            }
				};
				*/
			} elseif ($v[0] === Container::T_SERVICE) {
				// SERVICE 名称空间链实例
				return $this->$name = new class($name, ($int = (int) $v[1]) > 0 ? $int : 1) {
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
			}
			return $this->$name = Container::make($name);
		} else {
			$config = Config::get('getter');
			if (isset($config['providers_name'])) {
				$n = $config['providers_name'];
				if ($n && isset($this->$n) && isset($this->$n[$name])) {
					return $this->$name = Container::makeCustomProvider($this->$n[$name]);
				}
			}
		    if (isset($config['common_providers'][$name])) {
				return $this->$name = Container::makeCustomProvider($config['common_providers'][$name]);
			}
		}
		throw new \Exception("Undefined property: $$name");
    }
}
