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
    protected $auth;
    protected $server;
    protected $auto_create_dir = true;
    
    public function __construct($config)
    {
        $this->server = trim($config['server']);
        if (substr($this->server, -1) !== '/') {
            $this->server .= '/';
        }
        if (isset($config['auto_create_dir'])) {
            $this->auto_create_dir = $config['auto_create_dir'];
        }
        $this->auth = 'Authorization: Basic '.base64_encode($config['username'].':'.$config['password']);
    }
    
    public function get($from, $to = null)
    {
        if ($to) {
            $client = Client::get($this->url($from))->headers([$this->auth]);
            return $client->save($to) ? true : $this->setError($client->getResult());
        }
        return $this->send('GET', $this->url($from));
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        if ($this->auto_create_dir || $this->mkdir($to)) {
            $client = Client::put($this->url($to))->timeout(30)->headers([$this->auth]);
            $is_buffer ? $client->body($from) : $client->stream($from);
            $result = $client->getResult();
            return $result['status'] === 201 ? true : $this->setError($result);
        }
        return false;
    }

    public function stat($from)
    {
        $stat = $this->send('HEAD', $this->url($from), null, null, null, true);
        return $stat ? [
            'type'  => $stat['headers']['Content-Type'],
            'size'  => (int) $stat['headers']['Content-Length'],
            'mtime' => strtotime($stat['headers']['Last-Modified']),
        ] : false;
    }
    
    public function copy($from, $to)
    {
        if ($this->auto_create_dir || $this->mkdir($to)) {
            return $this->send('COPY', $this->url($from), null, ['Destination: '.$this->url($to)]);
        }
        return false;
    }
    
    public function move($from, $to)
    {
        if ($this->auto_create_dir || $this->mkdir($to)) {
            return $this->send('MOVE', $this->url($from), null, ['Destination: '.$this->url($to)]);
        }
        return false;
    }
    
    public function delete($from)
    {
        return $this->send('DELETE', $this->url($from));
    }
    
    protected function mkdir($path)
    {
        return $this->send('MKCOL', dirname($this->url($path)).'/');
    }
    
    protected function url($path)
    {
        return $this->server.trim(trim($path), '/');
    }
    
    protected function send($method, $path, $body = null, $headers = null, $curlopt = null, $return_headers = false)
    {
        $headers[] = $this->auth;
        $result = Client::send($method, $path, $body, $headers, $curlopt, true, $return_headers);
        if ($result['status'] >= 200 && $result['status'] < 300) {
            switch ($method) {
                case 'GET':
                    return $result['body'];
                case 'PUT':
                    return true;
                case 'HEAD':
                    return $result['headers'];
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
        return $this->setError($result);
    }
    
    protected function setError($result)
    {
        return false;
    }
}
