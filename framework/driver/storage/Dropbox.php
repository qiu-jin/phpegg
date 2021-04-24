<?php
namespace framework\driver\storage;

use framework\core\http\Client;

class Dropbox extends Storage
{
	// 访问key
    protected $acckey;
	// 服务端点
    protected static $endpoint = 'https://%s.dropboxapi.com/2/files';
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
		parent::__construct($config);
        $this->acckey = $config['acckey'];
    }
    
    /* 
     * 读取
     */
    public function get($from, $to = null)
    {
        $response = $this->send('download', ['path' => $this->path($from)]);
        if (!$this->result(json_decode($response->headers['dropbox-api-result'], true))) {
            return false;
        }
        return $to === null ? $response->body : (bool) file_put_contents($to, $response->body);
    }
    
    /* 
     * 检查
     */
    public function has($from)
    {
        return $this->result($this->send('get_metadata', ['path' => $this->path($from)])->decode(), true);
    }
    
    /* 
     * 上传
     */
    public function put($from, $to, $is_buffer = false)
    {
        $params = ['path' => $this->path($to), 'mode' => 'overwrite'];
        return $this->result($this->send('upload', $params, $is_buffer ? $from : file_get_contents($from))->decode());
    }

    /* 
     * 获取属性
     */
    public function stat($from)
    {
		$result = $this->result($this->send('get_metadata', ['path' => $this->path($from)])->decode(), false);
        return $result ? ['size'  => (int) $result['size'], 'mtime' => strtotime($result['server_modified'])] : false;
    }
    
    /* 
     * 复制
     */
    public function copy($from, $to)
    {
        $params = ['from_path' => $this->path($from), 'to_path' => $this->path($to)];
        return $this->result($this->send('copy_v2', $params)->decode());
    }
    
    /* 
     * 移动
     */
    public function move($from, $to)
    {
        $params = ['from_path' => $this->path($from), 'to_path' => $this->path($to)];
        return $this->result($this->send('move_v2', $params)->decode());
    }
    
    /* 
     * 删除
     */
    public function delete($from)
    {
        return $this->result($this->send('delete_v2', ['path' => $this->path($from)])->decode());
    }
    
    /* 
     * 发送请求
     */
    protected function send($path, $params, $binary = null)
    {
        $headers['Authorization'] = "Bearer $this->acckey";
        if ($path === 'upload' || $path === 'download') {
            $type = 'content';
            $headers['Dropbox-API-Arg'] = json_encode($params);
        } else {
            $type = 'api';
        }
        $client = new Client('POST', sprintf(self::$endpoint, $type).$path);
        if ($type === 'api') {
            $client->json($params);
        } elseif($path === 'upload') {
            $client->body($binary, 'application/octet-stream');
            $client->timeout($this->timeout);
        } elseif($path === 'download') {
            $client->returnHeaders();
            $client->timeout($this->timeout);
        }
        if (($response = $client->headers($headers)->response())->status === 200) {
            return $response;
        }
        return error($client->error, 2);
    }
    
    /* 
     * 结果处理
     */
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
