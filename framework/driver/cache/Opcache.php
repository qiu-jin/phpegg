<?php
namespace framework\driver\cache;

use framework\util\Str;
use framework\util\File;

/*
 * 只支持 null bool int float string array 类型（支持serialize object，但unserialize为array）
 */
class Opcache extends Cache
{
	// 缓存文件目录
    protected $dir;
	// 缓存文件扩展名
    protected $ext = '.cache.php';
	// 使用真实文件名
    protected $use_real_name = false;
	// 强制处理数据类型安全
    protected $force_type_safe = false;

    /*
     * 初始化
     */
    public function __construct($config)
    {
		parent::__construct($config);
		$this->dir = Str::lastPad($config['dir'], '/');
		if (isset($config['ext'])) {
			$this->ext = $config['ext'];
		}
		$this->use_real_name = !empty($config['use_real_name']);
		if (isset($config['force_type_safe'])) {
			$this->force_type_safe = $config['force_type_safe'];
		}
    }
    
    /*
     * 获取
     */
    public function get($key, $default = null)
    {
        if (is_php_file($file = $this->filename($key))) {
            $cache = require($file);
            if ($expiration === 0 || $expiration > time()) {
                return $cache;
            }
        }
        return $default;
    }

    /*
     * 检查
     */
    public function has($key)
    {
        if (is_php_file($file = $this->filename($key))) {
            require($file);
            return $expiration === 0 || $expiration > time();
        }
        return false;
    }
    
    /*
     * 设置
     */
    public function set($key, $value, $ttl = null)
    {
        if ($this->force_type_safe) {
            $value = json_decode(json_encode($value), true);
        }
        $contents = sprintf('<?php $expiration = %d;'.PHP_EOL.'return %s;',
            ($t = $this->ttl($ttl)) == 0 ? 0 : $t + time(),
            var_export($value, true)
        );
        return file_put_contents($file = $this->filename($key), $contents, LOCK_EX) && opcache_compile_file($file);
    }

    /*
     * 删除
     */
    public function delete($key)
    {
        return is_php_file($file = $this->filename($key)) && opcache_invalidate($file, true) && unlink($file);
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
    public function clear()
    {
		$len = strlen($this->ext);
        File::clearDir($this->dir, function ($file) use ($len) {
			if (substr($file, - $len) === $this->ext) {
				opcache_invalidate($file, true);
				return true;
			}
        });
    }
    
    /*
     * 垃圾回收
     */
    public function gc()
    {
		$len = strlen($this->ext);
		$time = time();
        File::cleanDir($this->dir, function ($file) use ($len, $time) {
			if (substr($file, - $len) === $this->ext) {
	            require($file);
				if ($expiration <= $time && $expiration !== 0) {
					opcache_invalidate($file, true);
					return true;
				}
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
