<?php
namespace framework\core;

defined('app\env\GETTER_PROVIDERS_NAME') || define('app\env\GETTER_PROVIDERS_NAME', 'providers');

trait Getter
{
    /*
     * 获取Provider实例
     */
    public function __get($name)
    {
        $n = \app\env\GETTER_PROVIDERS_NAME;
        if (isset($this->$n) && isset($this->$n[$name])) {
			return Container::makeProviderInstance($this->$n[$name]);
        } elseif ($type = Container::getProviderType($name)) {
            if ($type !== 'model') {
				return $this->$name = Container::{"make$type"}($name);
            }
            $v = Container::getProviderValue('model', $name);
			// 模型名称空间链实例
			return $this->$name = new class($name, is_array($v) ? $v[1] : 1) {
	            private $_ns;
	            private $_depth;
	            public function __construct($name, $depth) {
	                $this->_ns[] = $name;
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
		throw new \Exception("Undefined property: $$name");
    }
}
