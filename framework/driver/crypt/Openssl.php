<?php
namespace framework\driver\crypt;

class Openssl extends Crypt
{
	// 初始化向量
    protected $iv;
	// 配置项
    protected $config = [
        'method'    => 'AES-128-CBC',
    ];
    
    /*
     * 加密
     */
    public function encrypt($data, $raw = false)
    {
        return openssl_encrypt(
            $this->serialize($data),
            $this->config['method'],
            $this->config['key'],
            (bool) $raw,
            $this->getIv()
        );
    }
    
    /*
     * 解密
     */
    public function decrypt($data, $raw = false)
    {
        return $this->unserialize(openssl_decrypt(
            $data,
            $this->config['method'],
            $this->config['key'],
            (bool) $raw,
            $this->getIv()
        ));
    }
    
    /*
     * 公钥加密
     */
    public function publicEncrypt($data)
    {
        $key = openssl_get_publickey($this->config['publickey']);
        return openssl_public_encrypt($this->serialize($data), $res, $key) ? $res : false;
    }
    
    /*
     * 公钥解密
     */
    public function publicDecrypt($data)
    {
        $key = openssl_get_publickey($this->config['publickey']);
        return openssl_public_decrypt($data, $res, $key) ? $this->unserialize($res) : false;
    }
    
    /*
     * 私钥加密
     */
    public function privateEncrypt($data)
    {
        $key = openssl_get_privatekey($this->config['privatekey']);
        return openssl_private_encrypt($this->serialize($data), $res, $key) ? $res : false;
    }
    
    /*
     * 私钥解密
     */
    public function privateDecrypt($data)
    {
        $key = openssl_get_privatekey($this->config['privatekey']);
        return openssl_private_decrypt($data, $res, $key) ? $this->unserialize($res) : false;
    }
    
    /*
     * 获取初始化向量
     */
    protected function getIv()
    {
        if (isset($this->iv)) {
            return $this->iv;
        }
        if (($len = openssl_cipher_iv_length($this->config['method'])) === 0) {
            return $this->iv = '';
        }
        return $this->iv = substr(md5($this->config['salt'] ?? '', true), 0, $len);
    }
}
