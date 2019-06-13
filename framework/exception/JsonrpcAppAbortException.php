<?php
namespace framework\exception;

class JsonrpcAppAbortException extends \Exception
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
