<?php
namespace framework\driver\storage;

use framework\core\http\Client;

class Qiniu extends Storage
{
    protected $bucket;
    protected $domain;
    protected $apiurl;
    protected $accesskey;
    protected $secretkey;
    protected $public_bucket = false;
    
    public function __construct($config)
    {
        $this->bucket = $config['bucket'];
        $this->domain = $config['domain'];
        $this->accesskey = $config['accesskey'];
        $this->secretkey = $config['secretkey'];
        if (!empty($config['public_bucket'])) {
            $this->public_bucket = true;
        }
        $scheme = empty($config['https']) ? 'https' : 'http';
        $region = isset($config['region']) ? '-'.$config['region'] : ''; 
        $apiurl['rs'] = "$scheme://rs.qbox.me";
        $apiurl['fetch'] = "$scheme://iovip$region.qbox.me";
        $apiurl['upload'] = "$scheme://up$region.qbox.me";
    }
    
    public function get($from, $to = null)
    {
        $url = $this->domain.'/'.$this->path($from);
        if (!$this->public_bucket) {
            $url .= '?token='.$this->sign($url);
        }
        $client = Client::get($url);
        if ($to) {
            return $client->save($to) ? true : false;
        } else {
            $result = $client->getResult();
            return $result['status'] === 200 ? $result['body'] : false; 
        }
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->path($to);
        $data = $this->encode(json_encode(['scope'=>$this->bucket.':'.$to, 'deadline'=>time()+3600]));
        $token = $this->sign($data).':'.$data;
        $client = Client::post($this->apiurl['upload'])->form(['token' => $token, 'key' => $to], $is_buffer)->file('file', $from)->timeout(30);
        $data = $result->getJson();
        if (isset($data['hash'])) {
            return true;
        }
        if (isset($data['error'])) {
            $this->log = $data['error'];
        } else {
            $clierr = $client->getError();
            $this->log = $clierr ? "$clierr[0]: $clierr[1]" : 'unknown error';
        }
        return false;
    }
    
    public function stat($from)
    {
        $data = $this->send($this->apiurl['rs'], '/stat/'.$this->bencode($from), 'GET');
        return $data ? ['size' => $data['fsize'], 'mtime' => substr($data['putTime'], 0 ,10)] : false;
    }

    public function move($from, $to)
    {
        return $this->send($this->apiurl['rs'], '/move/'.$this->bencode($from).'/'.$this->bencode($to));
    }
    
    public function copy($from, $to)
    {
        return $this->send($this->apiurl['rs'], '/copy/'.$this->bencode($from).'/'.$this->bencode($to));
    }
    
    public function delete($from)
    {
        return $this->send($this->apiurl['rs'], '/delete/'.$this->bencode($from));
    }
    
    public function fetch($from, $to)
    {
        $scheme = strtolower(strtok($from, '://'));
        if ($scheme === 'http' || $scheme === 'https') {
            return $this->send($this->apiurl['fetch'], '/fetch/'.$this->encode($from).'/to/'.$this->bencode($$to));
        }
        return parent::fetch($from, $to);
    }
    
    private function send($url, $resource, $method = 'POST')
    {
        $result = Client::send($method, $url.$resource, null, ['Authorization: QBox '.$this->sign($resource."\n")], ['timeout' => 15], true);
        if ($result['body']) {
            $data = json_decode($result['body'], true);
            if ($result['status'] === 200) {
                if ($data) {
                    return $method === 'POST' ? (bool) $data : $data;
                }
                return true;
            }
        }
        if (isset($data['error'])) {
            $this->log = $data['error'];
        } else {
            $this->log = isset($result['error']) ? $result['error'] : 'unknown error';
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
