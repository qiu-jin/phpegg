<?php
namespace framework\driver\storage;

use framework\core\http\Client;

class Qiniu extends Storage
{
    protected $bucket;
    protected $region;
    protected $acckey;
    protected $seckey;
    protected $public_read;
    protected static $endpoint = 'https://rs.qbox.me';
    
    protected function init($config)
    {
        $this->bucket = $config['bucket'];
        $this->domain = $config['domain'];
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        if (isset($config['region'])) {
            $this->region = '-'.$config['region'];
        }
        $this->public_read = $config['public_read'] ?? false;
    }

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
    
    public function has($from)
    {
        return (bool) $this->send(self::$endpoint, '/stat/'.$this->encode($from), null, 'GET');
    }

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

    public function move($from, $to)
    {
        return $this->send(self::$endpoint, '/move/'.$this->encode($from).'/'.$this->encode($to));
    }
    
    public function copy($from, $to)
    {
        return $this->send(self::$endpoint, '/copy/'.$this->encode($from).'/'.$this->encode($to));
    }
    
    public function delete($from)
    {
        return $this->send(self::$endpoint, '/delete/'.$this->encode($from));
    }
    
    public function fetch($from, $to)
    {
        if (substr($from, 0, 7) == 'http://' || substr($from, 0, 8) == 'https://') {
            $path = '/fetch/'.$this->base64Encode($from).'/to/'.$this->encode($to);
            return $this->send("https://iovip{$this->region}.qbox.me", $path);
        }
        return parent::fetch($from, $to);
    }
    
    protected function send($host, $path, $client_methods = null, $method = 'POST')
    {
        $client = new Client($method, $host.$path);
        if ($client_methods) {
            foreach ($client_methods as $client_method => $params) {
                $client->$client_method(...$params);
            }
        }
        if ($path) {
            $client->header('Authorization', 'QBox '.$this->sign($path."\n"));
        }
        if (($response = $client->response)->status === 200) {
            return $method === 'GET' ? $response->body : true;
        }
        // 忽略404错误（has stat方法）
        if ($response->status === 404 && strtok($path, '/') === 'stat') {
            return false;
        }
        $result = $response->json();
        return error($result['error'] ?? $client->error, 2);
    }
    
    protected function path($path)
    {
        $path = trim($path);
        return $path[0] !== '/' ? $path : substr($path, 1);
    }

    protected function encode($str)
    {
        return $this->base64Encode($this->bucket.':'.$this->path($str));
    }
    
    protected function sign($str)
    { 
        return $this->acckey.':'.$this->base64Encode(hash_hmac('sha1', $str, $this->seckey, true));
    }
    
    protected function base64Encode($str)
    { 
        return str_replace(array("+", "/"), array("-", "_"), base64_encode($str)); 
    }
}
