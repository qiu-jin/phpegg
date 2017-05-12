<?php
namespace framework\driver\storage;

use framework\util\File;
use framework\core\Error;
use framework\core\http\Client;

class S3 extends Storage
{
    protected $bucket;
    protected $region;
    protected $acckey;
    protected $seckey;
    protected $public_read = false;
    protected static $host = 's3.amazonaws.com';
    
    public function __construct($config)
    {
        $this->bucket = $config['bucket'];
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        $this->region = $config['region'];
    }
    
    public function get($from, $to = null)
    {
        $client_methods['timeout'] = [30];
        if ($to) {
            $client_methods['save'] = [$to];
        }
        return $this->send('GET', $from, null, $client_methods, !$this->public_read);
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $client_methods['timeout'] = [30];
        $headers['Content-Type'] = File::mime($from, $is_buffer);
        if ($is_buffer) {
            $client_methods['body'] = [$from];
            $headers['Content-Length'] = strlen($from);
            $headers['X-Amz-Content-Sha256'] = hash('sha256', $from);
            return $this->send('PUT', $to, $headers, $client_methods);
        }
        $fp = fopen($from, 'r');
        if ($fp) {
            $client_methods['stream'] = [$fp];
            $headers['Content-Length'] = filesize($from);
            $headers['X-Amz-Content-Sha256'] = hash_file('sha256', $from);
            $return = $this->send('PUT', $to, $headers, $client_methods);
            fclose($fp);
            return $return;
        }
        return false;
    }

    public function stat($from)
    {
        $stat = $this->send('HEAD', $from, null, ['return_headers' => [true]], !$this->public_read);
        return $stat ? [
            'type'  => $stat['headers']['Content-Type'],
            'size'  => $stat['headers']['Content-Length'],
            'mtime' => strtotime($stat['headers']['Last-Modified']),
        ] : false;
    }
    
    public function copy($from, $to)
    {
        return $this->send('PUT', $from, ['x-amz-copy-source', $this->bucket.$this->url($to)]);
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
        return $this->send('DELETE', $from);
    }
    
    protected function send($method, $path, $headers = [], $client_methods = [], $auth = true)
    {
        $client = new Client($method, "https://".self::$host."/$this->bucket/$path");
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
        return $this->setError($result);
    }
    
    protected function setError($result)
    {
        //Error::set();
        return false;
    }
    
    protected function setHeaders($method, $path, $headers)
    {
        $headers['Host'] = self::$host;
        $headers['X-Amz-Date'] = gmdate('Ymd\THis\Z');
        isset($headers['X-Amz-Content-Sha256']) || $headers['X-Amz-Content-Sha256'] = hash('sha256', '');
        ksort($headers);
        $tmparr = [];
        $sendheaders = [];
        $canonicalheaders = '';
        foreach ($headers as $k => $v) {
            $sendheaders[] = "$k: $v";
            $k = strtolower($k);
            $v = trim($v);
            $tmparr[] = $k;
            $canonicalheaders .= "$k:$v\n";
        }
        $signheaders = implode(';', $tmparr);
        $str = "$method\n/$this->bucket/$path\n\n$canonicalheaders\n$signheaders\n".$headers['X-Amz-Content-Sha256'];
        $date = substr($headers['X-Amz-Date'], 0, 8);
        $scope = "$date/$this->region/s3/aws4_request";
        $signstr = "AWS4-HMAC-SHA256\n{$headers['X-Amz-Date']}\n$scope\n".hash('sha256', $str);

        $datekey    = hash_hmac('sha256', $date, "AWS4$this->seckey", true);
        $regionkey  = hash_hmac('sha256', $this->region, $datekey, true);
        $servicekey = hash_hmac('sha256', 's3', $regionkey, true);
        $signkey    = hash_hmac('sha256', 'aws4_request', $servicekey, true);
        $signature  = hash_hmac('sha256', $signstr, $signkey);
        $sendheaders[] = "Authorization: AWS4-HMAC-SHA256 Credential=$this->acckey/$scope,SignedHeaders=$signheaders,Signature=$signature";
        return $sendheaders;
    }
}
