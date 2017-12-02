<?php
namespace framework\driver\storage;

use framework\core\http\Client;

class Qiniu extends Storage
{
    protected $bucket;
    protected $region;
    protected $acckey;
    protected $seckey;
    protected $public_read = false;
    protected static $endpoint = 'https://rs.qbox.me';
    
    public function __construct($config)
    {
        $this->bucket = $config['bucket'];
        $this->domain = $config['domain'];
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        if (isset($config['region'])) {
            $this->region = '-'.$config['region'];
        }
    }

    public function get($from, $to = null)
    {
        $methods['timeout'] = $this->timeout;
        if ($to) {
            $methods['save'] = $to;
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
        $to = $this->path($to);
        $str = $this->base64Encode(json_encode([
            'scope'     => $this->bucket.':'.$to,
            'deadline'  => time()+3600
        ]));
        $token = $this->sign($str).':'.$str;
        $methods['timeout'] = $this->timeout;
        $methods['form'] = [['token' => $token, 'key' => $to]];
        if ($is_buffer) {
            $methods['buffer'] = ['file', $from];
        } else {
            $methods['file'] = ['file', $from];
        }
        return $this->send("https://up{$this->region}.qbox.me", null, $methods);
    }
    
    public function stat($from)
    {
        $stat = $this->send(self::$endpoint, '/stat/'.$this->encode($from), null, 'GET');
        if ($stat) {
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
        if (stripos($from, 'http://') === 0 || stripos($from, 'https://') === 0) {
            $path = '/fetch/'.$this->base64Encode($from).'/to/'.$this->encode($to);
            return $this->send("https://iovip{$this->region}.qbox.me", $path);
        }
        return parent::fetch($from, $to);
    }
    
    protected function send($host, $path, $client_methods = [], $method = 'POST')
    {
        $client = new Client($method, $host.$path);
        if ($client_methods) {
            foreach ($client_methods as $client_method => $params) {
                $client->$client_method(... (array) $params);
            }
        }
        if ($path) {
            $client->header('Authorization', 'QBox '.$this->sign($path."\n"));
        }
        $response = $client->response;
        if ($response->status === 200) {
            return $method === 'GET' ? $response->body : true;
        }
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
