<?php
namespace framework\driver\crypt;

/*
 * PHP < 7.2 with `pecl install libsodium`
 */
class Sodium extends Crypt
{
    protected $nonce;
    
    public function encrypt($data, $raw = false)
    {
        $secret = sodium_crypto_secretbox(
            $this->serialize($data),
            $this->getNonce(),
            $this->config['key']
        );
        return $raw ? $secret : base64_encode($secret);
    }
    
    public function decrypt($data, $raw = false)
    {
        return $this->unserialize(sodium_crypto_secretbox_open(
            $raw ? $data : base64_decode($data),
            $this->getNonce(),
            $this->config['key']
        ));
    }
    
    protected function getNonce()
    {
        return $this->nonce ?? $this->nonce = sodium_crypto_generichash($this->config['nonce'] ?? '', null, 24);
    }
}
