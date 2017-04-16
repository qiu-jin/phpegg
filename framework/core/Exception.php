<?php
namespace framework\core;

class Exception extends \Exception
{
    const DB = 1000;
    const RPC = 1010;
    const CACHE = 1020;
    const QUEUE = 1030;
    const STORAGE = 1040;
    
    protected static $code_name = [
        self::DB =>  'Db',
        self::RPC => 'Rpc',
        self::CACHE => 'Cache',
        self::QUEUE => 'Queue',
        self::STORAGE => 'Storage'
    ];
    
    protected $data;
    
    public function __construct($message, $code = null, $data = null)
    {
        
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function setData($data)
    {
        $this->data = $data;
    }
    
    public function getName($code = null)
    {
        if (!$code) {
            $code = $this->getCode();
        }
        if ($code && isset(self::$code_name[$code])) {
            return self::$code_name[$code];
        }
        return null;
    }
    
    public static function setName($code, $name)
    {
        self::$code_name[$code] = $name;
    }
}
