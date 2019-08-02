<?php
namespace framework\driver\storage;

use framework\util\Str;
use framework\util\Arr;
use framework\util\File;

class Local extends Storage
{
	// 目录
    protected $dir;
    
    /* 
     * 构造方法
     */
    public function __construct($config)
    {
        $this->dir = rtrim($config['dir'], '/');
        if (isset($config['domain'])) {
            $this->domain = $config['domain'];
        }
        if (!File::isWritableDir($this->dir)) {
            throw new \Exception("Local storage dir $this->dir is not writable");
        }
    }
    
    /* 
     * 读取
     */
    public function get($from, $to = null)
    {
        return $to ? copy($this->path($from), $to) : file_get_contents($this->path($from));
    }
    
    /* 
     * 检查
     */
    public function has($from)
    {
        return file_exists($this->path($from));
    }
    
    /* 
     * 上传
     */
    public function put($from, $to, $is_buffer = false)
    {
        if (File::makeDir($to = $this->path($to))) {
            return $is_buffer ? (bool) file_put_contents($to, $from) : copy($from, $to);
        }
        return false;
    }

    /* 
     * 获取属性
     */
    public function stat($from)
    {
        return ($stat = stat($this->path($from))) ? Arr::fitlerKeys($stat, ['size', 'mtime', 'ctime']) : false;
    }
    
    /* 
     * 复制
     */
    public function copy($from, $to)
    {
        return File::makeDir($to = $this->path($to)) && copy($this->path($from), $to);
    }
    
    /* 
     * 移动
     */
    public function move($from, $to)
    {
        return File::makeDir($to = $this->path($to)) && rename($this->path($from), $to);
    }
    
    /* 
     * 删除
     */
    public function delete($from)
    {
        return unlink($this->path($from));
    }
    
    /* 
     * 获取路径
     */
    protected function path($path)
    {
        return $this->dir.parent::path($path);
    }
}
