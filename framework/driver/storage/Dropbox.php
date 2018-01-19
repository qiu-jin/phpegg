<?php
namespace framework\driver\storage;

use framework\core\http\Client;

class Dropbox extends Storage
{
    protected $acckey;
    protected static $endpoint = 'https://%s.dropboxapi.com/2/files';
    
    public function __construct($config)
    {
        $this->acckey = $config['acckey'];
    }
    
    public function get($from, $to = null)
    {
        $response = $this->send('download', ['path' => $this->path($from)]);
        if (!$this->result(json_decode($response->headers['Dropbox-API-Result']))) {
            return false;
        }
        return $to ? (bool) file_put_contents($to, $response->body) : $response->body;
        
    }
    
    public function has($from)
    {
        return $this->result($this->send('get_metadata', ['path' => $this->path($from)])->json(), true);
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $data = $is_buffer ? $from : file_get_contents($from);
        return $this->result($this->send('upload', ['path' => $this->path($to), 'mode' => 'overwrite'], $data)->json());
    }

    public function stat($from)
    {
        return $this->result($this->send('get_metadata', ['path' => $this->path($from)])->json(), false);
    }
    
    public function copy($from, $to)
    {
        return $this->result($this->send('copy_v2', ['from_path' => $this->path($from), 'to_path' => $this->path($to)])->json());
    }
    
    public function move($from, $to)
    {
        return $this->result($this->send('move_v2', ['from_path' => $this->path($from), 'to_path' => $this->path($to)])->json());
    }
    
    public function delete($from)
    {
        return $this->result($this->send('delete_v2', ['path' => $this->path($from)])->json());
    }
    
    protected function send($path, $params, $binary = null)
    {
        $endpoint = sprintf(self::$endpoint, $binary === null ? 'api' : 'content');
        $client   = new Client('POST', $endpoint.$path);
        if ($binary === null) {
            $client->json($params);
        } elseif($path === 'upload') {
            $client->body($binary, 'application/octet-stream');
        } elseif($path === 'download') {
            $client->returnHeaders();
        }
        $client->header('Authorization', "Bearer $this->acckey");
        if (($response = $client->response)->status === 200) {
            return $response;
        }
        return error($client->error, 2);
    }
    
    protected function result($data, $return_bool = null)
    {
        if (empty($data)) {
            return false;
        }
        if (isset($data['error'])) {
            if ($return_bool !== null) {
                return false;
            }
            return error($data['error_summary'], 2);
        }
        return $return_bool === false ? $data : true; 
    }
}
