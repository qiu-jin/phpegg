<?php
namespace framework\driver\cache;

use framework\util\Str;
use framework\util\File as UFile;

class File extends Cache
{
	// 缓存文件目录
    protected $dir;
	// 缓存文件扩展名
    protected $ext = '.cache.txt';
    // 序列化反序列化处理器
    protected $serializer = ['serialize', 'unserialize'];
	// 使用真实文件名
    protected $use_real_name = false;
    
    /*
     * 初始化
     */
    public function __construct($config)
    {
		parent::__construct($config);
		if (isset($config['ext'])) {
			$this->ext = $config['ext'];
		}
		if (isset($config['serializer'])) {
			$this->serializer = $config['serializer'];
		}
        $this->dir = Str::lastPad($config['dir'], '/');
		$this->use_real_name = !empty($config['use_real_name']);
    }
    
    /*
     * 获取
     */
    public function get($key, $default = null)
    {
        if (is_file($file = $this->filename($key)) && ($fp = fopen($file, 'r'))) {
            $expiration = (int) trim(fgets($fp));
            if($expiration === '0' || $expiration > time()){
                $data = stream_get_contents($fp);
                fclose($fp);
                return ($this->serializer[1])($data);
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
            return $expiration === '0' || $expiration > time();
        }
        return false;
    }
    
    /*
     * 设置
     */
    public function set($key, $value, $ttl = null)
    {
        $expiration = ($t = $this->ttl($ttl)) == 0 ? 0 : $t + time();
        return (bool) file_put_contents($this->filename($key), $expiration.PHP_EOL.($this->serializer[0])($value), LOCK_EX);
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
    public function clear(callable $fitler = null)
    {
		$len = strlen($this->ext);
        UFile::clearDir($this->dir, function ($file) use ($len) {
			return substr($file, - $len) === $this->ext;
		});
    }
    
    /*
     * 垃圾回收
     */
    public function gc()
    {
		$len = strlen($this->ext);
		$time = time();
        UFile::clearDir($this->dir, function ($file) use ($len, $time) {
			if (substr($file, - $len) === $this->ext) {
				$fp = fopen($file, 'r');
	            $expiration = (int) trim(fgets($fp));
	            fclose($fp);
	            return $expiration <= $time && $expiration !== 0;
			}
        });
    }
    
    /*
     * 获取文件名
     */
    protected function filename($key)
    {
        return $this->dir.($this->use_real_name ? $key : md5($key)).$this->ext;
    }
}
