<?php
namespace framework\driver\storage;

use framework\core\http\Client;

class Qiniu extends Storage
{
    protected $bucket;
    protected $domain;
    protected $acckey;
    protected $seckey;
    protected $region;
    protected $public_read = false;
    
    public function __construct($config)
    {
        $this->bucket = $config['bucket'];
        $this->domain = $config['domain'];
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        isset($config['region']) && $this->region = '-'.$config['region'];
    }

    public function get($from, $to = null)
    {
        $client_methods['timeout'] = [30];
        if ($to) {
            $client_methods['save'] = [$to];
        }
        $url = "$this->domain/$from";
        if (!$this->public_read) {
            $url .= '?token='.$this->sign($url);
        }
        return $this->send($url, null, $client_methods, 'GET');
    }

    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->path($to);
        $str = $this->base64Encode(json_encode(['scope'=>$this->bucket.':'.$to, 'deadline'=>time()+3600]));
        $token = $this->sign($str).':'.$str;
        
        $client_methods['timeout'] = [30];
        $client_methods['form'] = [['token' => $token, 'key' => $to], $is_buffer];
        $client_methods['file'] = [$from];
        return $this->send("https://up{$this->region}.qbox.me", null, $client_methods);
    }
    
    public function stat($from)
    {
        $stat = $this->send('https://rs.qbox.me', '/stat/'.$this->path($from), 'GET');
        if ($stat) {
            $stat = jsondecode($stat);
            return [
                'type'  => $stat['mimeType'],
                'size'  => $stat['fsize'],
                'mtime' => substr($stat['putTime'], 0 ,10),
            ];
        }
        return false;
    }

    public function move($from, $to)
    {
        return $this->send('https://rs.qbox.me', '/move/'.$this->path($from).'/'.$this->path($to));
    }
    
    public function copy($from, $to)
    {
        return $this->send('https://rs.qbox.me', '/copy/'.$this->path($from).'/'.$this->path($to));
    }
    
    public function delete($from)
    {
        return $this->send('https://rs.qbox.me', '/delete/'.$this->path($from));
    }
    
    public function fetch($from, $to)
    {
        if (stripos($from, 'http://') === 0 || stripos($from, 'https://') === 0) {
            return $this->send("https://iovip{$this->region}.qbox.me", '/fetch/'.$this->path($$to));
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
        $result = $client->getResult();
        if ($result['status'] === 200) {
            return $method === 'GET' ? $result['body'] : true;
        }
        return $this->setError($result);
    }

    protected function path($str)
    {
        return $this->base64Encode($this->bucket.':'.parent::path($str));
    }
    
    protected function sign($str)
    { 
        return $this->acckey.':'.$this->base64Encode(hash_hmac('sha1', $str, $this->seckey, true));
    }
    
    protected function setError($result)
    {
        return false;
    }
    
    protected function base64Encode($str)
    { 
        return str_replace(array("+", "/"), array("-", "_"), base64_encode($str)); 
    }
}
