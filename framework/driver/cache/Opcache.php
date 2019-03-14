<?php
namespace framework\driver\cache;

use framework\util\Str;
use framework\util\File;

class Opcache extends Cache
{
	// 缓存文件目录
    protected $dir;
	// 缓存文件扩展名
    protected $ext = '.cache.php';
	// 启用值过滤功能
    protected $enable_filter_value = false;

    /*
     * 初始化
     */
    protected function __init($config)
    {
		$this->dir = Str::lastPad($config['dir'], '/');
		if (isset($config['ext'])) {
			$this->ext = $config['ext'];
		}
		if (isset($config['enable_filter_value'])) {
			$this->enable_filter_value = $config['enable_filter_value'];
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
            return $expiration === 0 || $expiration < time();
        }
        return false;
    }
    
    /*
     * 设置
     */
    public function set($key, $value, $ttl = null)
    {
        if ($this->enable_filter_value) {
            $this->filterValue($value);
        }
        $contents = sprintf('<?php $expiration = %d;'.PHP_EOL.'return %s;',
            ($t = $ttl ?? $this->ttl) == 0 ? 0 : $t + time(),
            var_export($value, true)
        );
        return file_put_contents($file = $this->filename($key), $contents) && opcache_compile_file($file);
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
    public function clean()
    {
        File::cleanDir($this->dir, function ($file) {
            opcache_invalidate($file, true);
        });
    }
    
    /*
     * 垃圾回收
     */
    public function gc()
    {
        $maxtime = time() + $this->gc_maxlife;
        File::cleanDir($this->dir, function ($file) use ($maxtime) {
            if ($maxtime > filemtime($file)) {
                return true;
            }
            opcache_invalidate($file, true);
        });
    }

    /*
     * 获取文件名
     */
    protected function filename($key)
    {
        return $this->dir.md5($key).$this->ext;
    }
    
    /*
     * 过滤设置值
     */
    protected function filterValue(&$value)
    {
        if (is_array($value)) {
            foreach ($value as $val) {
                $this->filterValue($val);
            }
        } elseif (is_object($value)) {
            $value = get_object_vars($value);
            foreach ($value as $val) {
                $this->filterValue($val);
            }
        } elseif (is_resource($value)) {
            $value = null;
        }
    }
}
