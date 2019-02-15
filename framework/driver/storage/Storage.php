<?php
namespace framework\driver\storage;

use framework\util\Str;
use framework\core\Container;
use framework\core\http\Client;

abstract class Storage
{
    protected $domain;
    protected $timeout;
    
    /* 
     * 读取文件（文件不存在会触发错误或异常）
     * $from 要读取的storage文件路径
     * $to 本地磁盘文件路径，如果为空，返回文件读取的文件内容
     *     如果不为空，方法读取的文件内容保存到$to的本地磁盘文件路径中，返回true或false
     */
    abstract public function get($from, $to = null);
    
    /* 
     * 检查文件是否存在（文件不存在不会触发错误或异常）
     */
    abstract public function has($from);
    
    /* 
     * 获取文件元信息
     * 返回array包含，size：文件大小，type：文件类型，mtime：文件更新时间 等信息
     */
    abstract public function stat($from);
    
    /* 
     * 上传更新文件
     * $from 本地文件，如果 $is_buffer为false，$from为本地磁盘文件路径
     *       如果 $is_buffer为true，$from为要上传的buffer内容
     * $to 上传后储存的storage路径
     */
    abstract public function put($from, $to, $is_buffer = false);
    
    /* 
     * 复制storage文件，从$from复制到$to
     */
    abstract public function copy($from, $to);
    
    /* 
     * 移动storage文件，从$from移动到$to
     */
    abstract public function move($from, $to);

    /* 
     * 删除storage文件
     */
    abstract public function delete($from);
    
    
    public function __construct($config)
    {
        $this->init($config);
        if (isset($config['timeout'])) {
            $this->timeout = $config['timeout'];
        }
    }
    
    /* 
     * 获取storage文件访问url
     */
    public function url($path)
    {
        return $this->domain.$this->path($path);
    }
    
    /* 
     * 获取domain
     */
    public function domain()
    {
        return $this->domain;
    }
    
    /* 
     * 抓取远程文件并保存到storage
     * 支持http https和所有storage配置实例
     */
    public function fetch($from, $to)
    {
        if (strpos($from, '://')) {
            list($scheme, $uri) = explode('://', $from, 2);
            if ($scheme === 'http' || $scheme === 'https') {
                $data = Client::get($from);
            } else {
                $data = Container::driver('storage', $scheme)->get($uri);
            }
        } else {
            $data = Container::driver('storage')->get($uri);
        }
        return $data ? $this->put($data, $to, true) : false;
    }
    
    public function timeout($timeout)
    {
        return $this->timeout = $timeout;
    }
    
    protected function path($path)
    {
        return Str::headPad(trim($path), '/');
    }
}
