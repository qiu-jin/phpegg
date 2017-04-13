<?php
namespace Framework\Driver\Crypt;

class Openssl extends Crypt
{   
    private $config = [
        'method' => 'AES-128-CBC',
    ];
    
    public function __construct($config)
    {
        if (!extension_loaded('openssl')) {
            throw new \Exception('openssl extension not loaded');
        }
        if (isset($config['key'])) {
            $this->config['key'] = $config['key'];
        } else {
            throw new \Exception('Openssl Crypt no password');
        }
        if (isset($config['method'])) {
            $this->config['method'] = $config['method'];
        }
        if (isset($config['serialize'])) {
            list($this->serialize, $this->unserialize) = $config['serialize'];
        }
        $this->config['iv'] = openssl_digest(empty($config['salt']) ? $config['key'] : $config['salt'], 'MD5', true);
    }
    
    public function encrypt($data, $raw = false)
    {
        return openssl_encrypt($this->serialize($data),
                               $this->config['method'],
                               $this->config['key'],
                               $raw ? 1 : 0,
                               $this->config['iv']
                           );
    }
    
    public function decrypt($data, $raw = false)
    {
        return openssl_decrypt($this->unserialize($data),
                               $this->config['method'],
                               $this->config['key'],
                               $raw ? 1 : 0,
                               $this->config['iv']
                           );
    }
}
