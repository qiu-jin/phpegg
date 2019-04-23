<?php
namespace framework\core\exception;

class JsonRpcAbortException extends \Exception
{
    protected $data;
    
    public function __construct($code, $message, $data = null)
    {
		$this->data = $data;
		parent::__construct($message, $code);
    }
	
    public function getData()
    {
        return $this->data;
    }
}
