<?php
namespace framework\driver\crypt;

abstract class Crypt
{
	// 配置项
    protected $config = [];
         
    /*
     * 加密
     */
    abstract public function encrypt($data);
    
    /*
     * 解密
     */
    abstract public function decrypt($data);
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config + $this->config;
    }
    
    /*
     * 序列化
     */
    protected function serialize($data)
    {
        return isset($this->config['serializer']) ? ($this->config['serializer'][0])($data) : $data;
    }
    
    /*
     * 反序列化
     */
    protected function unserialize($data)
    {
        return isset($this->config['serializer']) ? ($this->config['serializer'][1])($data) : $data;
    }
}
