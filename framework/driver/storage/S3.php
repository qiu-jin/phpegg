<?php
namespace framework\driver\storage;

use framework\util\Xml;
use framework\util\File;
use framework\core\http\Client;

class S3 extends Storage
{
	// 桶
    protected $bucket;
	// 地区
    protected $region;
	// 访问key
    protected $acckey;
	// 加密key
    protected $seckey;
	// 服务端点
    protected $endpoint;
	// 是否公共读取
    protected $public_read = false;
    
    /* 
     * 构造方法
     */
    public function __construct($config)
    {
		parent::__construct($config);
        $this->bucket = $config['bucket'];
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        $this->region = $config['region'];
        $this->endpoint = $config['endpoint'] ?? 'https://s3.amazonaws.com';
        $this->domain   = $config['domain']   ?? "$this->endpoint/$this->bucket";
        if (isset($config['public_read'])) {
            $this->public_read = $config['public_read'];
        }
    }
    
    /* 
     * 读取
     */
    public function get($from, $to = null)
    {
        $methods['timeout'] = [$this->timeout];
        if ($to) {
            $methods['save'] = [$to];
        }
        return $this->send('GET', $from, null, $methods, !$this->public_read);
    }
    
    /* 
     * 检查
     */
    public function has($from)
    {
        return $this->send('HEAD', $from, null, ['curlopt' => [CURLOPT_NOBODY, 1]], !$this->public_read);
    }
    
    /* 
     * 上传
     */
    public function put($from, $to, $is_buffer = false)
    {
        $methods['timeout'] = [$this->timeout];
        $headers['Content-Type'] = File::mime($from, $is_buffer);
        if ($is_buffer) {
            $methods['body'] = [$from];
            $headers['Content-Length'] = strlen($from);
            $headers['X-Amz-Content-Sha256'] = hash('sha256', $from);
            return $this->send('PUT', $to, $headers, $methods);
        }
        if ($fp = fopen($from, 'r')) {
            $methods['stream'] = [$fp];
            $headers['Content-Length'] = filesize($from);
            $headers['X-Amz-Content-Sha256'] = hash_file('sha256', $from);
            $return = $this->send('PUT', $to, $headers, $methods);
            fclose($fp);
            return $return;
        }
        return false;
    }

    /* 
     * 获取属性
     */
    public function stat($from)
    {
        $stat = $this->send('HEAD', $from, null, [
            'returnHeaders' => [true], 'curlopt' => [CURLOPT_NOBODY, true]
        ], !$this->public_read);
        return $stat ? [
            'type'  => $stat['Content-Type'],
            'size'  => $stat['Content-Length'],
            'mtime' => strtotime($stat['Last-Modified']),
        ] : false;
    }
    
    /* 
     * 复制
     */
    public function copy($from, $to)
    {
        return $this->send('PUT', $to, ['X-Amz-Copy-Source' => '/'.$this->bucket.$this->path($from)]);
    }
    
    /* 
     * 移动
     */
    public function move($from, $to)
    {
        return $this->copy($from, $to) && $this->delete($from);
    }
    
    /* 
     * 删除
     */
    public function delete($from)
    {
        return $this->send('DELETE', $from);
    }
    
    /* 
     * 发送请求
     */
    protected function send($method, $path, $headers = null, $client_methods = null, $auth = true)
    {
        $path = $this->path($path);
        $client = new Client($method, "$this->endpoint/$this->bucket$path");
        if ($auth) {
            $client->headers($this->setHeaders($method, $path, $headers));
        }
        if ($client_methods) {
            foreach ($client_methods as $client_method => $params) {
                $client->$client_method(...$params);
            }
        }
        $response = $client->response();
        if ($response->status >= 200 && $response->status < 300) {
            switch ($method) {
                case 'GET':
                    return $response->body;
                case 'HEAD':
                    return isset($client_methods['returnHeaders']) ? $response->headers : true;
				case 'PUT':	
                case 'DELETE':
                    return true;
            }
        }
        // HEAD请求忽略404错误（has stat方法）
        if ($response->status === 404 && $method === 'HEAD') {
            return false;
        }
        $result = Xml::decode($response->body);
        return error($result['Message'] ?? $client->error , 2);
    }
    
    /* 
     * 设置请求header
     */
    protected function setHeaders($method, $path, $headers)
    {
        $headers['Host'] = parse_url($this->endpoint, PHP_URL_HOST);
        $headers['X-Amz-Date'] = gmdate('Ymd\THis\Z');
        if (!isset($headers['X-Amz-Content-Sha256'])) {
            $headers['X-Amz-Content-Sha256'] = hash('sha256', '');
        }
        ksort($headers);
        $canonicalheaders = '';
        foreach ($headers as $k => $v) {
            $k = strtolower($k);
            $v = trim($v);
            $headerkeys[] = $k;
            $canonicalheaders .= "$k:$v\n";
        }
		$algo 			= 'AWS4-HMAC-SHA256';
        $signedheaders  = implode(';', $headerkeys);
        $str            = "$method\n/$this->bucket$path\n\n$canonicalheaders\n$signedheaders\n"
                        . $headers['X-Amz-Content-Sha256'];
        $date           = substr($headers['X-Amz-Date'], 0, 8);
        $scope          = "$date/$this->region/s3/aws4_request";
        $signstr        = "$algo\n{$headers['X-Amz-Date']}\n$scope\n".hash('sha256', $str);
        $datekey        = hash_hmac('sha256', $date, "AWS4$this->seckey", true);
        $regionkey      = hash_hmac('sha256', $this->region, $datekey, true);
        $servicekey     = hash_hmac('sha256', 's3', $regionkey, true);
        $signkey        = hash_hmac('sha256', 'aws4_request', $servicekey, true);
        $signature      = hash_hmac('sha256', $signstr, $signkey);
		$headers['Authorization'] = "$algo Credential=$this->acckey/$scope,SignedHeaders=$signedheaders,Signature=$signature";
        return $headers;
    }
}
