<?php
namespace framework\driver\storage;

use framework\util\Xml;
use framework\util\File;
use framework\core\Error;
use framework\core\http\Client;

class Oss extends Storage
{
    protected $bucket;
    protected $acckey;
    protected $seckey;
    protected $endpoint;
    protected $public_read = false;
    
    public function __construct($config)
    {
        $this->bucket = $config['bucket'];
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        $this->endpoint = $config['endpoint'];
    }
    
    public function get($from, $to = null)
    {
        $client_methods['timeout'] = 30;
        if ($to) {
            $client_methods['save'] = $to;
        }
        return $this->send('GET', $from, null, $client_methods, !$this->public_read);
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $client_methods['timeout'] = 30;
        $headers['Content-Type'] = File::mime($from, $is_buffer);
        if ($is_buffer) {
            $client_methods['body'] = $from;
            $headers['Content-Length'] = strlen($from);
            $headers['Content-Md5'] = base64_encode(md5($from, true));
            return $this->send('PUT', $to, $headers, $client_methods);
        }
        $fp = fopen($from, 'r');
        if ($fp) {
            $client_methods['stream'] = $fp;
            $headers['Content-Length'] = filesize($from);
            $headers['Content-Md5'] = base64_encode(md5_file($from, true));
            $return = $this->send('PUT', $to, $headers, $client_methods);
            fclose($fp);
            return $return;
        }
    }

    public function stat($from)
    {
        $stat = $this->send('HEAD', $from, null, ['return_headers' => true], !$this->public_read);
        return $stat ? [
            'type'  => $stat['Content-Type'],
            'size'  => (int) $stat['Content-Length'],
            'mtime' => strtotime($stat['Last-Modified']),
        ] : false;
    }

    public function move($from, $to)
    {
        return $this->copy($from, $to) && $this->delete($from);
    }
    
    public function copy($from, $to)
    {
        return $this->send('PUT', $to, ['x-oss-copy-source' => '/'.$this->bucket.$this->path($from)]);
    }

    public function delete($from)
    {
        return $this->send('DELETE', $from);
    }
    
    protected function send($method, $path, $headers = [], $client_methods = [], $auth = true)
    {
        $path = $this->path($path);
        $client = new Client($method, 'http://'.$this->bucket.'.'.$this->endpoint.$path);
        if ($client_methods) {
            foreach ($client_methods as $client_method => $params) {
                $client->$client_method(... (array) $params);
            }
        }
        if ($auth) {
            $client->headers($this->setHeaders($method, $path, $headers));
        }
        $result = $client->getResult();
        if ($result['status'] >= 200 && $result['status'] < 300) {
            switch ($method) {
                case 'GET':
                    return $result['body'];
                case 'PUT':
                    return true;
                case 'HEAD':
                    return $result['headers'];
                case 'DELETE':
                    return true;
            }
        }
        return $result['status'] !== 404 && $this->setError($result);
    }
    
    protected function setError($result)
    {
        if ($result['body']) {
            $data = Xml::decode($result['body']);
            if ($data) {
                return (bool) Error::set($data['Code'].': '.$data['Message'], Error::ERROR, 3);
            }
        }
        $error = isset($result['error']) ? $result['error'][0].': '.$result['error'][1] : 'unknown error';
        return (bool) Error::set($error, Error::ERROR, 3);
    }
    
    protected function setHeaders($method, $path, $headers)
    {
        $headers['Date'] = gmdate('D, d M Y H:i:s').' GMT';
        $str = "$method\n";
        $str .= isset($headers['Content-Md5']) ? $headers['Content-Md5']."\n" : "\n";
        $str .= isset($headers['Content-Type']) ? $headers['Content-Type']."\n" : "\n";
        $str .= $headers['Date']."\n";
        $str .= isset($headers['x-oss-copy-source']) ? 'x-oss-copy-source:'.$headers['x-oss-copy-source']."\n" : "";
        $str .= '/'.$this->bucket.$path;
        $sendheaders[] = "Authorization: OSS $this->acckey:".base64_encode(hash_hmac('sha1', $str, $this->seckey, true));
        foreach ($headers as $k => $v) {
            $sendheaders[] = "$k: $v";
        }
        return $sendheaders;
    }
}