<?php
namespace framework\core;

abstract class Model implements ArrayAccess
{
	protected static $db;
	
    public static function __init()
	{
        if (self::$init) {
            return;
        }
        self::$init = true;
    }
	
    public static function name($table)
	{
		
    }
	
    public static function __callStatic($method, $params)
    {
		return (new static())->__call($method, $params);
    }
	
    public function __construct($data = null)
	{
		$this->__invoke($data);
    }
	
	public function __invoke($data = null)
	{
		
	}
	
	public function __call($method, $params)
	{
		self::$db->model($this)->$method(...$params);
	}
	
    public function __get()
	{
		
    }
	
    public function __set()
	{
		
    }
	
    public function __isset()
	{
		
    }
	
    public function __unset()
	{

    }
	
	public function offsetExists($offset)
	{
		
	}
	
	public function offsetGet($offset)
	{
		
	}
	
	public function offsetSet($offset, $value)
	{
		
	}
	
	public function offsetUnset($offset)
	{
		
	}
	
	public function save($data = null)
	{
		
	}
}
Model::__init();

