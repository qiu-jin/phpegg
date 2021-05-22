<?php
namespace framework\driver\cache;

use framework\util\Str;
use framework\util\File as F;

class File extends Cache
{
	// 缓存文件目录
    protected $dir;
	// 缓存文件扩展名
    protected $ext = '.cache.txt';
    
    /*
     * 初始化
     */
    protected function __init($config)
    {
		if (isset($config['ext'])) {
			$this->ext = $config['ext'];
		}
        $this->dir = Str::lastPad($config['dir'], '/');
    }
    
    /*
     * 获取
     */
    public function get($key, $default = null)
    {
        if (is_file($file = $this->filename($key)) && ($fp = fopen($file, 'r'))) {
            $expiration = (int) trim(fgets($fp));
            if($expiration === '0' || $expiration < time()){
                $data = stream_get_contents($fp);
                fclose($fp);
                return $this->unserialize($data);
            } else {
                fclose($fp);
            }
        }
        return $default;
    }

    /*
     * 检查
     */
    public function has($key)
    {
        if (is_file($file = $this->filename($key)) && ($fp = fopen($file, 'r'))) {
            $expiration = (int) trim(fgets($fp));
            fclose($fp);
            return $expiration === '0' || $expiration < time();
        }
        return false;
    }
    
    /*
     * 设置
     */
    public function set($key, $value, $ttl = null)
    {
        $expiration = ($t = $this->ttl($ttl)) == 0 ? 0 : $t + time();
        return (bool) file_put_contents($this->filename($key), $expiration.PHP_EOL.$this->serialize($value));
    }

    /*
     * 删除
     */
    public function delete($key)
    {
        return is_file($file = $this->filename($key)) && unlink($file);
    }
    
    /*
     * 自增
     */
    public function increment($key, $value = 1)
    {
        return $this->set($key, $this->get($key, 0) + $value);
    }
    
    /*
     * 自减
     */
    public function decrement($key, $value = 1)
    {
        return $this->set($key, $this->get($key, 0) - $value);
    }

    /*
     * 清理
     */
    public function clean()
    {
        F::cleanDir($this->dir);
    }
    
    /*
     * 垃圾回收
     */
    public function gc()
    {
        $maxtime = time() + $this->gc_maxlife;
        F::cleanDir($this->dir, function ($file) use ($maxtime) {
            return $maxtime > filemtime($file);
        });
    }
    
    /*
     * 获取文件名
     */
    protected function filename($key)
    {
        return $this->dir.md5($key).$this->ext;
    }
}
