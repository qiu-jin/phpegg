<?php
namespace framework\driver\storage;

use framework\core\http\Client;

/*
 * Apache:  https://httpd.apache.org/docs/2.4/mod/mod_dav.html
 * Ngnix:   https://nginx.org/en/docs/http/ngx_http_dav_module.html
 * IIS:     https://www.iis.net/learn/install/installing-publishing-technologies/installing-and-configuring-webdav-on-iis
 * Box:     https://www.box.com/dav
 * OneDrive:https://d.docs.live.net
 * Pcloud:  https://webdav.pcloud.com
 * 坚果云:   https://dav.jianguoyun.com/dav
 */
class Webdav extends Storage
{
    protected $endpoint;
    protected $username;
    protected $password;
    protected $public_read = false;
    protected $auto_create_dir = false;
    
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
    
    public function get($from, $to = null)
    {
        $methods['timeout'] = [$this->timeout];
        if ($to) {
            $methods['save'] = [$to];
        }
        return $this->send('GET', $this->uri($from), null, $methods, !$this->public_read);
    }
    
    public function has($from)
    {
        return $this->send('HEAD', $this->uri($from), null, ['curlopt' => [CURLOPT_NOBODY, true]], !$this->public_read);
    }
    
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

    public function stat($from)
    {
        $stat = $this->send('HEAD', $this->uri($from), null, [
            'returnHeaders' => [true], 'curlopt' => [CURLOPT_NOBODY, true]
        ], !$this->public_read);
        return $stat ? [
            'type'  => $stat['Content-Type'],
            'size'  => (int) $stat['Content-Length'],
            'mtime' => strtotime($stat['Last-Modified']),
        ] : false;
    }
    
    public function copy($from, $to)
    {
        return $this->ckdir($to = $this->uri($to)) && $this->send('COPY', $this->uri($from), ['Destination' => $to]);
    }
    
    public function move($from, $to)
    {
        return $this->ckdir($to = $this->uri($to)) && $this->send('MOVE', $this->uri($from), ['Destination' => $to]);
    }
    
    public function delete($from)
    {
        return $this->send('DELETE', $this->uri($from));
    }
    
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
    
    protected function uri($path)
    {
        return $this->endpoint.$this->path($path);
    }
    
    protected function ckdir($path)
    {
        return $this->auto_create_dir || $this->send('MKCOL', dirname($path).'/');
    }
}
