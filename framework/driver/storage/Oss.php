<?php
namespace framework\driver\storage;

use framework\util\Xml;
use framework\util\File;
use framework\core\http\Client;

class Oss extends Storage
{
	// 桶
    protected $bucket;
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
        $this->bucket 	= $config['bucket'];
        $this->acckey 	= $config['acckey'];
        $this->seckey 	= $config['seckey'];
        $this->endpoint = $config['endpoint'];
        $this->domain   = $config['domain'] ?? "http://{$config['bucket']}.{$config['endpoint']}";
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
            $headers['Content-Md5'] = base64_encode(md5($from, true));
            return $this->send('PUT', $to, $headers, $methods);
        }
        if ($fp = fopen($from, 'r')) {
            $methods['stream'] = [$fp];
            $headers['Content-Length'] = filesize($from);
            $headers['Content-Md5'] = base64_encode(md5_file($from, true));
            $return = $this->send('PUT', $to, $headers, $methods);
            fclose($fp);
            return $return;
        }
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
            'size'  => (int) $stat['Content-Length'],
            'mtime' => strtotime($stat['Last-Modified']),
        ] : false;
    }
	
    /* 
     * 复制
     */
    public function copy($from, $to)
    {
        return $this->send('PUT', $to, ['x-oss-copy-source' => '/'.$this->bucket.$this->path($from)]);
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
        $client = new Client($method, 'http://'.$this->bucket.'.'.$this->endpoint.$path);
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
        return error("[{$result['Code']}] {$result['Message']}" ?? $client->error, 2);
    }

    /* 
     * 设置请求header
     */
    protected function setHeaders($method, $path, $headers)
    {
        $headers['Date'] = gmdate('D, d M Y H:i:s').' GMT';
        $str = "$method\n"
             . ($headers['Content-Md5'] ?? '')."\n"
             . ($headers['Content-Type'] ?? '')."\n"
             . $headers['Date']."\n"
             . (isset($headers['x-oss-copy-source']) ? "x-oss-copy-source:{$headers['x-oss-copy-source']}\n" : '')
             . '/'.$this->bucket.$path;
        $headers['Authorization'] = "OSS $this->acckey:".base64_encode(hash_hmac('sha1', $str, $this->seckey, true));
        return $headers;
    }
}
