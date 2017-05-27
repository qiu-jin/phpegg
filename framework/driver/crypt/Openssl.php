<?php
namespace framework\driver\crypt;

class Openssl extends Crypt
{   
    protected $option = [
        'method' => 'AES-128-CBC',
    ];
    
    protected function init($config)
    {
        if (isset($config['key'])) {
            $this->option['key'] = $config['key'];
        } else {
            throw new \Exception('Openssl Crypt no password');
        }
        if (isset($config['method'])) {
            $this->option['method'] = $config['method'];
        }
        $this->option['iv'] = openssl_digest(empty($config['salt']) ? $config['key'] : $config['salt'], 'MD5', true);
    }
    
    public function encrypt($data, $raw = false)
    {
        return openssl_encrypt($this->serialize($data),
                               $this->option['method'],
                               $this->option['key'],
                               $raw ? 1 : 0,
                               $this->option['iv']
                           );
    }
    
    public function decrypt($data, $raw = false)
    {
        return openssl_decrypt($this->unserialize($data),
                               $this->option['method'],
                               $this->option['key'],
                               $raw ? 1 : 0,
                               $this->option['iv']
                           );
    }
}
