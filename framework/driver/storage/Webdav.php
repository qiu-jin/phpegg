<?php
namespace framework\driver\storage;

use framework\core\http\Client;

/*
 * Box:     https://www.box.com/dav
 * OneDrive:https://d.docs.live.net
 * Pcloud:  https://webdav.pcloud.com
 * 坚果云:   https://dav.jianguoyun.com/dav
 */
class Webdav extends Storage
{
	// 服务端点
    protected $endpoint;
	// 账号
    protected $username;
	// 密码
    protected $password;
	// 是否公共读取
    protected $public_read = false;
	// 是否自动创建目录
    protected $auto_create_dir = false;
    
    /* 
     * 构造方法
     */
    public function __construct($config)
    {
		parent::__construct($config);
        $this->endpoint = $config['endpoint'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->domain   = $config['domain'] ?? $this->endpoint;
        if (isset($config['public_read'])) {
            $this->public_read = $config['public_read'];
        }
        if (isset($config['auto_create_dir'])) {
            $this->auto_create_dir = $config['auto_create_dir'];
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
        return $this->send('GET', $this->uri($from), null, $methods, !$this->public_read);
    }
    
    /* 
     * 检查
     */
    public function has($from)
    {
        return $this->send('HEAD', $this->uri($from), null, ['curlopt' => [CURLOPT_NOBODY, true]], !$this->public_read);
    }
    
    /* 
     * 上传
     */
    public function put($from, $to, $is_buffer = false)
    {
        if ($this->ckdir($to = $this->uri($to))) {
            $methods['timeout'] = [$this->timeout];
            if ($is_buffer) {
                $methods['body'] = [$from];
                return $this->send('PUT', $to, null, $methods);
            }
            if ($fp = fopen($from, 'r')) {
                $methods['stream'] = [$fp];
                $return = $this->send('PUT', $to, null, $methods);
                fclose($fp);
                return $return;
            }
        }
        return false;
    }

    /* 
     * 获取属性
     */
    public function stat($from)
    {
		$methods = ['returnHeaders' => [true], 'curlopt' => [CURLOPT_NOBODY, true]];
        if ($stat = $this->send('HEAD', $this->uri($from), null, $methods, !$this->public_read)) {
	        return [
	            'type'  => $stat['Content-Type'],
	            'size'  => (int) $stat['Content-Length'],
	            'mtime' => strtotime($stat['Last-Modified']),
	        ];
        }
        return false;
    }
    
    /* 
     * 复制
     */
    public function copy($from, $to)
    {
        return $this->ckdir($to = $this->uri($to)) && $this->send('COPY', $this->uri($from), ['Destination' => $to]);
    }
    
    /* 
     * 移动
     */
    public function move($from, $to)
    {
        return $this->ckdir($to = $this->uri($to)) && $this->send('MOVE', $this->uri($from), ['Destination' => $to]);
    }
    
    /* 
     * 删除
     */
    public function delete($from)
    {
        return $this->send('DELETE', $this->uri($from));
    }
    
    /* 
     * 发送请求
     */
    protected function send($method, $url, $headers = null, $client_methods = null, $auth = true)
    {
        $client = new Client($method, $url);
        if ($auth) {
			$client->auth($this->username, $this->password);
        }
        if ($headers) {
            $client->headers($headers);
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
                case 'COPY':
                    return true;
                case 'MOVE':
                    return true;
                case 'MKCOL':
                    return true;
                case 'DELETE':
                    return true;
            }
        }
        // HEAD请求忽略404错误（has stat方法）
        if ($response->status === 404 && $method === 'HEAD') {
            return false;
        }
        return error($client->error, 2);
    }
    
    /* 
     * 获取uri
     */
    protected function uri($path)
    {
        return $this->endpoint.$this->path($path);
    }
    
    /* 
     * 检查目录
     */
    protected function ckdir($path)
    {
        return $this->auto_create_dir || $this->send('MKCOL', dirname($path).'/');
    }
}
