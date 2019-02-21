<?php
namespace framework\driver\storage;

use framework\core\http\Client;

class Qiniu extends Storage
{
	// 桶
    protected $bucket;
	// 地区
    protected $region;
	// 访问key
    protected $acckey;
	// 加密key
    protected $seckey;
	// 是否公共读取
    protected $public_read = false;
	// 服务端点
    protected static $endpoint = 'https://rs.qbox.me';
    
    /* 
     * 构造方法
     */
    public function __construct($config)
    {
		parent::__construct($config);
        $this->bucket = $config['bucket'];
        $this->domain = $config['domain'];
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        if (isset($config['region'])) {
            $this->region = '-'.$config['region'];
        }
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
        $url = $this->domain.parent::path($from);
        if (!$this->public_read) {
            $url .= '?token='.$this->sign($url);
        }
        return $this->send($url, null, $methods, 'GET');
    }
    
    /* 
     * 检查
     */
    public function has($from)
    {
        return (bool) $this->send(self::$endpoint, '/stat/'.$this->encode($from), null, 'GET');
    }

    /* 
     * 上传
     */
    public function put($from, $to, $is_buffer = false)
    {
        $to  = $this->path($to);
        $str = $this->base64Encode(json_encode([
            'scope'     => "$this->bucket:$to",
            'deadline'  => time() + 3600
        ]));
        return $this->send("https://up{$this->region}.qbox.me", null, [
            'timeout'   => [$this->timeout],
            'form'      => [[
                'token' => $this->sign($str).":$str", 
                'key'   => $to
            ]],
            ($is_buffer ? 'buffer' : 'file') => ['file', $from]
        ]);
    }
    
    /* 
     * 获取属性
     */
    public function stat($from)
    {
        if ($stat = $this->send(self::$endpoint, '/stat/'.$this->encode($from), null, 'GET')) {
            $stat = jsondecode($stat);
            return [
                'type'  => $stat['mimeType'],
                'size'  => $stat['fsize'],
                'mtime' => (int) substr($stat['putTime'], 0 ,10),
            ];
        }
        return false;
    }
	
    /* 
     * 复制
     */
    public function copy($from, $to)
    {
        return $this->send(self::$endpoint, '/copy/'.$this->encode($from).'/'.$this->encode($to));
    }

    /* 
     * 移动
     */
    public function move($from, $to)
    {
        return $this->send(self::$endpoint, '/move/'.$this->encode($from).'/'.$this->encode($to));
    }
    
    /* 
     * 删除
     */
    public function delete($from)
    {
        return $this->send(self::$endpoint, '/delete/'.$this->encode($from));
    }
    
    /* 
     * 抓取
     */
    public function fetch($from, $to)
    {
        if (substr($from, 0, 7) == 'http://' || substr($from, 0, 8) == 'https://') {
            $path = '/fetch/'.$this->base64Encode($from).'/to/'.$this->encode($to);
            return $this->send("https://iovip{$this->region}.qbox.me", $path);
        }
        return parent::fetch($from, $to);
    }
    
    /* 
     * 发送请求
     */
    protected function send($host, $path, $client_methods = null, $method = 'POST')
    {
        $client = new Client($method, $host.$path);
        if ($path) {
            $client->header('Authorization', 'QBox '.$this->sign($path."\n"));
        }
        if ($client_methods) {
            foreach ($client_methods as $client_method => $params) {
                $client->$client_method(...$params);
            }
        }
        if (($response = $client->response())->status === 200) {
            return $method === 'GET' ? $response->body : true;
        }
        // 忽略404错误（has stat方法）
        if ($response->status === 404 && strtok($path, '/') === 'stat') {
            return false;
        }
        $result = $response->json();
        return error($result['error'] ?? $client->error, 2);
    }
    
    /* 
     * 获取路径
     */
    protected function path($path)
    {
        $path = trim($path);
        return $path[0] !== '/' ? $path : substr($path, 1);
    }

    /* 
     * 编码数据
     */
    protected function encode($str)
    {
        return $this->base64Encode($this->bucket.':'.$this->path($str));
    }
    
    /* 
     * 签名数据
     */
    protected function sign($str)
    { 
        return $this->acckey.':'.$this->base64Encode(hash_hmac('sha1', $str, $this->seckey, true));
    }
    
    /* 
     * base64编码
     */
    protected function base64Encode($str)
    { 
        return str_replace(array("+", "/"), array("-", "_"), base64_encode($str)); 
    }
}
