<?php
namespace framework\driver\crypt;

class Openssl extends Crypt
{
    protected $key;
    protected $iv;
    protected $method = 'AES-128-CBC';
    
    protected function init($config)
    {
        if (isset($config['key'])) {
            $this->key = $config['key'];
        } else {
            throw new \Exception('Crypt no key');
        }
        $this->iv = $config['iv'] ?: openssl_digest($config['key'], 'MD5', true);
        if (isset($config['method'])) {
            $this->method = $config['method'];
        }
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
