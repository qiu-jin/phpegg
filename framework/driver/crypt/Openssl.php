<?php
namespace framework\driver\crypt;

class Openssl extends Crypt
{
    protected $iv;
    protected $config = [
        'method'    => 'AES-128-CBC',
    ];
    
    public function encrypt($data, $raw = false)
    {
        return openssl_encrypt(
            $this->serialize($data),
            $this->config['method'],
            $this->config['key'],
            intval($raw),
            $this->getIv()
        );
    }
    
    public function decrypt($data, $raw = false)
    {
        return $this->unserialize(openssl_decrypt(
            $data,
            $this->config['method'],
            $this->config['key'],
            intval($raw),
            $this->getIv()
        ));
    }
    
    public function publicEncrypt($data)
    {
        $key = openssl_get_publickey($this->config['publickey']);
        return openssl_public_encrypt($this->serialize($data), $res, $key) ? $res : false;
    }
    
    public function publicDecrypt($data)
    {
        $key = openssl_get_publickey($this->config['publickey']);
        return openssl_public_decrypt($data, $res, $key) ? $this->unserialize($res) : false;
    }
    
    public function privateEncrypt($data)
    {
        $key = openssl_get_privatekey($this->config['privatekey']);
        return openssl_private_encrypt($this->serialize($data), $res, $key) ? $res : false;
    }
    
    public function privateDecrypt($data)
    {
        $key = openssl_get_privatekey($this->config['privatekey']);
        return openssl_private_decrypt($data, $res, $key) ? $this->unserialize($res) : false;
    }
    
    protected function getIv()
    {
        if (isset($this->iv)) {
            return $this->iv;
        }
        if (($len = openssl_cipher_iv_length($this->config['method'])) === 0) {
            return $this->iv = '';
        }
        return $this->iv = substr(openssl_digest($this->config['salt'] ?? '', 'MD5', true), 0, $len);
    }
}
