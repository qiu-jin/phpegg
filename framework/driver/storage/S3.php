<?php
namespace framework\driver\storage;

use framework\core\http\Client;

class S3 extends Storage
{
    protected $host;
    protected $bucket;
    
    public function __construct($config)
    {
        $this->host = $config['host'].'/';
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
        return $this->send('PUT', $from);
    }

    public function stat($from)
    {
        $stat = $this->send('HEAD', $from, null, null, null, true);
        return $stat ? [
            'size' => $stat['headers']['Content-Length'],
            'mtime' => strtotime($result['headers']['Last-Modified']),
            'type' => $stat['headers']['Content-Type'],
        ] : false;
    }
    
    public function copy($from, $to)
    {
        return $this->send('PUT', $from, null, ['x-amz-copy-source: '.$this->bucket.$this->url($to)], ['nobody' => 1]);
    }
    
    public function move($from, $to)
    {
        if ($this->copy($from, $to)) {
            return (bool) $this->delete($from);
        }
        return false;
    }
    
    public function delete($from)
    {
        return $this->send('DELETE', $from, null, null, ['nobody' => 1]);
    }
    
    protected function send($method, $path, $body = null, $headers = null, $curlopt = null, $return_headers = false)
    {
        $headers[] = $this->auth;
        $result = Client::send($method, $this->url($path), $body, $headers, $curlopt, true, $return_headers);
        if ($result['status'] >= 200 && $result['status'] < 300) {
            switch ($method) {
                case 'GET':
                    return $result['body'];
                case 'PUT':
                    return true;
                case 'HEAD':
                    return $result['headers'];
                case 'COPY':
                    return true;
                case 'MOVE':
                    return true;
                case 'DELETE':
                    return true;
            }
        }
        $this->log = isset($result['error']) ? $result['error'] : 'unknown error';
        return false;
    }
}