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
        $this->domain = $config['domain'] ?? $config['host'];
    }
    
    public function get($from, $to = null)
    {
        $methods['timeout'] = $this->timeout;
        if ($to) {
            $methods['save'] = $to;
        }
        return $this->send('GET', $this->uri($from), null, $methods, !$this->public_read);
    }
    
    public function has($from)
    {
        return $this->send('HEAD', $this->uri($from), null, null, !$this->public_read);
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->uri($to);
        if ($this->ckdir($to)) {
            $methods['timeout'] = $this->timeout;
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
        $stat = $this->send('HEAD', $this->uri($from), null, ['returnHeaders' => true], !$this->public_read);
        return $stat ? [
            'type'  => $stat['Content-Type'],
            'size'  => (int) $stat['Content-Length'],
            'mtime' => strtotime($stat['Last-Modified']),
        ] : false;
    }
    
    public function copy($from, $to)
    {
        $to = $this->uri($to);
        return $this->ckdir($to) && $this->send('COPY', $this->uri($from), ['Destination: '.$to]);
    }
    
    public function move($from, $to)
    {
        $to = $this->uri($to);
        return $this->ckdir($to) && $this->send('MOVE', $this->uri($from), ['Destination: '.$to]);
    }
    
    public function delete($from)
    {
        return $this->send('DELETE', $this->uri($from));
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
        $status = $client->gerStatus();
        if ($status >= 200 && $status < 300) {
            switch ($method) {
                case 'GET':
                    return $client->getBody();
                case 'PUT':
                    return true;
                case 'HEAD':
                    return isset($client_methods['returnHeaders']) ? $client->headers : true;
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
        if ($status === 404 && $method === 'HEAD' && !isset($client_methods['returnHeaders'])) {
            return false;
        }
        return error($status ?? $client->getErrorInfo(), 2);
    }
    
    protected function uri($path)
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
