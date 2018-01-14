<?php
namespace framework\driver\crypt;

/*
 * https://github.com/jedisct1/libsodium-php
 */
class Sodium extends Crypt
{   
    protected $key;
    protected $nonce;
    
    protected function init($config)
    {
        $this->key = $config['key'];
        $this->nonce = $config['nonce'] ?? sodium_crypto_generichash($config['key'], null, 24);
    }
    
    public function encrypt($data, $raw = false)
    {
        $secret = sodium_crypto_secretbox($this->serialize($data), $this->nonce, $this->key);
        return $raw ? $secret : base64_encode($secret);
    }
    
    public function decrypt($data, $raw = false)
    {
        return $this->unserialize(sodium_crypto_secretbox_open($raw ? $data : base64_decode($data), $this->nonce, $this->key));
    }
}
