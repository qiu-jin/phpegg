<?php
namespace framework\extend\misc;

use framework\core\Container as CoreContainer;

trait Container
{
    public function __get($name)
    {
        if (isset($this->connections[$name])) {
            if (in_array(self::$_connection_type[$name])) {
                return $this->$name = CoreContainer::handler($name, $this->connections[$name]);
            }
            if (isset($this->connections[$name]['type'])) {
                return $this->$name = CoreContainer::handler($this->connections[$name]['type'], $this->connections[$name]['config']);
            }
        } elseif (isset(self::$_connection_type[$name])) {
            return $this->$name = CoreContainer::handler($name);
        }
        throw new \Exception('Illegal attr: '.$name);
    }
}
