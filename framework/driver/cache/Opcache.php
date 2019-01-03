<?php
namespace framework\driver\cache;

use framework\util\Str;
use framework\util\File;

class Opcache extends Cache
{
	// 缓存文件目录
    protected $dir;
	// 缓存文件扩展名
    protected $ext;
	// 启用值过滤功能
    protected $enable_filter_value;

    protected function init($config)
    {
        $this->dir = Str::lastPad($config['dir'], '/');
        $this->ext = $config['ext'] ?? '.cache.php';
        $this->enable_filter_value = $config['enable_filter_value'] ?? false;
    }
    
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
    
    public function set($key, $value, $ttl = null)
    {
        if ($this->enable_filter_value) {
            $this->filterValue($value);
        }
        $contents = sprintf('<?php $expiration = %d;'.PHP_EOL.'return %s;',
            $ttl ? $ttl + time() : 0,
            var_export($value, true)
        );
        return file_put_contents($file = $this->filename($key), $contents) && opcache_compile_file($file);
    }

    public function has($key)
    {
        if (is_php_file($file = $this->filename($key))) {
            require($file);
            return $expiration === 0 || $expiration < time();
        }
        return false;
    }
    
    public function delete($key)
    {
        return is_php_file($file = $this->filename($key)) && opcache_invalidate($file, true) && unlink($file);
    }
    
    public function increment($key, $value = 1)
    {
        return $this->set($key, $this->get($key, 0) + $value);
    }
    
    public function decrement($key, $value = 1)
    {
        return $this->set($key, $this->get($key, 0) - $value);
    }
    
    public function clean()
    {
        File::cleanDir($this->dir, function ($file) {
            opcache_invalidate($file, true);
        });
    }
    
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

    protected function filename($key)
    {
        return $this->dir.md5($key).$this->ext;
    }
    
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
            throw new \Exception('Not allow value is a resource');
        }
    }
}
