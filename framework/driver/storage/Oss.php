<?php
namespace framework\driver\storage;

use framework\util\Xml;
use framework\util\File;
use framework\core\http\Client;

class Oss extends Storage
{
    protected $bucket;
    protected $acckey;
    protected $seckey;
    protected $endpoint;
    protected $public_read;
    
    public function __construct($config)
    {
        $this->bucket = $config['bucket'];
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        $this->endpoint = $config['endpoint'];
        $this->domain   = $config['domain'] ?? "'http://{$config['bucket']}.{$config['endpoint']}";
        $this->public_read = $config['public_read'] ?? false;
    }
    
    public function get($from, $to = null)
    {
        $methods['timeout'] = $this->timeout;
        if ($to) {
            $methods['save'] = $to;
        }
        return $this->send('GET', $from, null, $methods, !$this->public_read);
    }
    
    public function has($from)
    {
        return $this->send('HEAD', $from, null, ['curlopt' => [CURLOPT_NOBODY, 1]], !$this->public_read);
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $methods['timeout'] = $this->timeout;
        $headers['Content-Type'] = File::mime($from, $is_buffer);
        if ($is_buffer) {
            $methods['body'] = $from;
            $headers['Content-Length'] = strlen($from);
            $headers['Content-Md5'] = base64_encode(md5($from, true));
            return $this->send('PUT', $to, $headers, $methods);
        }
        if ($fp = fopen($from, 'r')) {
            $methods['stream'] = $fp;
            $headers['Content-Length'] = filesize($from);
            $headers['Content-Md5'] = base64_encode(md5_file($from, true));
            $return = $this->send('PUT', $to, $headers, $methods);
            fclose($fp);
            return $return;
        }
    }

    public function stat($from)
    {
        $stat = $this->send('HEAD', $from, null, [
            'returnHeaders' => true, 'curlopt' => [CURLOPT_NOBODY, true]
        ], !$this->public_read);
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
        $response = $client->response;
        if ($response->status >= 200 && $response->status < 300) {
            switch ($method) {
                case 'GET':
                    return $response->body;
                case 'PUT':
                    return true;
                case 'HEAD':
                    return isset($client_methods['returnHeaders']) ? $response->headers : true;
                case 'DELETE':
                    return true;
            }
        }
        // HEAD请求忽略404错误（has stat方法）
        if ($response->status === 404 && $method === 'HEAD') {
            return false;
        }
        $result = Xml::decode($response->body);
        return error($result['Message'] ?? $client->error, 2);
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
