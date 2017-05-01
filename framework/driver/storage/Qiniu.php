<?php
namespace framework\driver\storage;

use framework\core\http\Client;

class Qiniu extends Storage
{
    private $domain;
    private $bucket;
    private $accesskey;
    private $secretkey;
    private $private = true;
    private $rsurl = 'http://rs.qbox.me';
    private $fetchurl = 'http://iovip.qbox.me';
    private $uploadurl = 'http://up.qiniu.com';
    
    public function __construct($config)
    {
        $this->domain = $config['domain'];
        $this->bucket = $config['bucket'];
        $this->accesskey = $config['accesskey'];
        $this->secretkey = $config['secretkey'];
        if (isset($config['rsurl'])) {
            $this->rsurl = $config['rsurl'];
        }
        if (isset($config['fetchurl'])) {
            $this->fetchurl = $config['fetchurl'];
        }
        if (isset($config['uploadurl'])) {
            $this->uploadurl = $config['uploadurl'];
        }
    }
    
    public function get($from, $to = null)
    {
        $from = $this->path($from);
        $content = Client::send('GET', $this->domain.'/'.$from);
        if ($to && $content) {
            return file_put_contents($to, $content);
        }
        return $content;
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->path($to);
        $data = $this->encode(json_encode(['scope'=>$this->bucket.':'.$to, 'deadline'=>time()+3600]));
        $token = $this->sign($data).':'.$data;
        $res = Client::post($this->uploadurl)->form(['token' => $token, 'key' => $to], $is_buffer)->file('file', $from)->timeout(30);
        return $res->status == 200 && !empty($res->json['hash']);
    }
    
    public function stat($from)
    {
        $res = $this->send($this->rsurl, '/stat/'.$this->bencode($from), 'GET');
        return $res ? ['size' => $res['fsize'], 'mtime' => substr($res['putTime'], 0 ,10)] : false;
    }

    public function move($from, $to)
    {
        return $this->send($this->rsurl, '/move/'.$this->bencode($from).'/'.$this->bencode($to));
    }
    
    public function copy($from, $to)
    {
        return $this->send($this->rsurl, '/copy/'.$this->bencode($from).'/'.$this->bencode($to));
    }
    
    public function delete($from)
    {
        return $this->send($this->rsurl, '/delete/'.$this->bencode($from));
    }
    
    public function fetch($from, $to)
    {
        $scheme = strtolower(strtok($from, '://'));
        if ($scheme === 'http' || $scheme === 'https') {
            return $this->send($this->fetchurl, '/fetch/'.$this->encode($from).'/to/'.$this->bencode($$to));
        }
        return parent::fetch($from, $to);
    }
    
    private function send($url, $resource, $method = 'POST')
    {
        $res = Client::send($method, $url.$resource, null, ['Authorization: QBox '.$this->sign($resource."\n")], ['timeout' => 15], true);
        if ($res['status'] == 200) {
            $data = json_decode($res['body'], true);
            if ($data) {
                return $method === 'POST' ? (bool) $data : $data;
            }
            return true;
        }
        return false;
    }
    
    private function sign($str)
    { 
        $digest = hash_hmac('sha1', $str, $this->secretkey, true);
        return $this->accesskey.':'.$this->encode($digest);
    }
    
    private function encode($str)
    { 
        return str_replace(array("+", "/"), array("-", "_"), base64_encode($str)); 
    }
    
    private function bencode($str)
    {
        $str = $this->path($str);
        return $this->encode($this->bucket.':'.$str);
    }
}
