<?php
namespace framework\driver\storage;

use framework\core\http\Client;

class Webdav extends Storage
{
    protected $host;
    protected $headers;
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->headers = [
            'Authorization: Basic '.base64_encode($config['username'].':'.$config['password']),
        ]
    }
    
    public function get($from, $to = null)
    {
        $data = $this->send('GET', $from);
        if ($data) {
            if ($to) {
                return file_put_contents($to, $data);
            }
            return $data;
        }
        return false;
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $data = $this->send('PUT', $from);
    }

    public function stat($from)
    {
        $data = $this->send('HEAD', $from);
    }
    
    public function copy($from, $to)
    {
        $data = $this->send('COPY', $from);
    }
    
    public function move($from, $to)
    {
        $data = $this->send('MOVE', $from);
    }
    
    public function delete($from)
    {
        $data = $this->send('DELETE', $from);
    }
    
    protected function send($method, $uri, $body = null, $headers = [], $curlopt = [], $return_headers = false)
    {
        $headers = $headers ? $this->headers + $headers : $this->headers;
        $result = Client::send($method, $this->host, $body, $headers, $curlopt, true, $return_headers);
    }
    
    protected function lock($path)
    {
        
    }
    
    protected function chdir($path)
    {
        
    }
}
