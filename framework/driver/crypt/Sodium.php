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
        if (isset($config['key'])) {
            $this->key = $config['key'];
        } else {
            throw new \Exception('Crypt no key');
        }
        $this->nonce = $config['nonce'] ?: sodium_crypto_generichash($config['key'], null, 24);
    }
    
    public function encrypt($data, $raw = false)
    {
        $secret = sodium_crypto_secretbox($this->serialize($data), $this->nonce, $this->key);
        return $raw ? $secret : bin2hex($secret);
    }
    
    public function decrypt($data, $raw = false)
    {
        return $this->unserialize(sodium_crypto_secretbox_open($raw ? $data : hex2bin($data), $this->nonce, $this->key));
    }
}
