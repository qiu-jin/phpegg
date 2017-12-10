<?php
namespace framework\driver\crypt;

class Openssl extends Crypt
{
    protected $key;
    protected $iv;
    protected $method;
    
    protected function init($config)
    {
        $this->key = $config['key'];
        $this->iv  = $config['iv'] ?? openssl_digest($config['key'], 'MD5', true);
        $this->method = $config['method'] ?? 'AES-128-CBC';
    }
    
    public function encrypt($data, $raw = false)
    {
        return openssl_encrypt($this->serialize($data), $this->method, $this->key, $raw ? 1 : 0, $this->iv);
    }
    
    public function decrypt($data, $raw = false)
    {
        return $this->unserialize(openssl_decrypt($data, $this->method, $this->key, $raw ? 1 : 0, $this->iv));
    }
}
