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
 * Dropbox:
 * GoogleDrive: 
 */
class Webdav extends Storage
{
    protected $host;
    protected $username;
    protected $password;
    protected $public_read = false;
    protected $auto_create_dir = false;
    
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        if (isset($config['auto_create_dir'])) {
            $this->auto_create_dir = (bool) $config['auto_create_dir'];
        }
    }
    
    public function get($from, $to = null)
    {
        $methods['timeout'] = 30;
        if ($to) {
            $methods['save'] = $to;
        }
        return $this->send('GET', $this->url($from), null, $methods, !$this->public_read);
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->url($to);
        if ($this->ckdir($to)) {
            $methods['timeout'] = 30;
            if ($is_buffer) {
                $methods['body'] = $from;
                return $this->send('PUT', $to, null, $methods);
            }
            $fp = fopen($from, 'r');
            if ($fp) {
                $methods['stream'] = $fp;
                $return = $this->send('PUT', $to, null, $methods);
                fclose($fp);
                return $return;
            }
        }
        return false;
    }

    public function stat($from)
    {
        $stat = $this->send('HEAD', $this->url($from), null, ['returnHeaders' => true], !$this->public_read);
        return $stat ? [
            'type'  => $stat['Content-Type'],
            'size'  => (int) $stat['Content-Length'],
            'mtime' => strtotime($stat['Last-Modified']),
        ] : false;
    }
    
    public function copy($from, $to)
    {
        $to = $this->url($to);
        return $this->ckdir($to) ? $this->send('COPY', $this->url($from), ['Destination: '.$to]) : false;
    }
    
    public function move($from, $to)
    {
        $to = $this->url($to);
        return $this->ckdir($to) ? $this->send('MOVE', $this->url($from), ['Destination: '.$to]) : false;
    }
    
    public function delete($from)
    {
        return $this->send('DELETE', $this->url($from));
    }
    
    protected function send($method, $url, $headers = [], $client_methods = [], $auth = true)
    {
        $client = new Client($method, $url);
        if ($client_methods) {
            foreach ($client_methods as $client_method => $params) {
                $client->$client_method(... (array) $params);
            }
        }
        if ($auth) {
            $headers[] = $this->auth();
        }
        if ($headers) {
            $client->headers($headers);
        }
        $status = $client->status;
        if ($status >= 200 && $status < 300) {
            switch ($method) {
                case 'GET':
                    return $client->body;
                case 'PUT':
                    return true;
                case 'HEAD':
                    return $client->headers;
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
        if ($status === 404 && $method === 'HEAD') {
            return false;
        }
        return error($status ? $status : $client->error, 2);
    }
    
    protected function url($path)
    {
        return $this->host.'/'.trim(trim($path), '/');
    }
    
    protected function auth()
    {
        return 'Authorization: Basic '.base64_encode("$this->username:$this->password");
    }
    
    protected function ckdir($path)
    {
        return $this->auto_create_dir || $this->send('MKCOL', dirname($path).'/');
    }
}
