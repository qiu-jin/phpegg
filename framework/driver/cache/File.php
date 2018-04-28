<?php
namespace framework\driver\cache;


class File extends Cache
{
    protected $dir;
    protected $ext;
    protected $gc_maxlife;

    protected function init($config)
    {
        if (!is_dir($config['dir']) || !is_writable($config['dir'])) {
            throw new \Exception('Cache dir is not writable');
        }
        $this->dir = $config['dir'];
        if (substr($this->dir, -1) !== '/') {
            $this->dir .= '/';
        }
        $this->ext = $config['ext'] ?? '.cache.txt';
        $this->gc_maxlife = $config['gc_maxlife'] ?? 2592000;
    }
    
    public function get($key, $default = null)
    {
        if (is_file($file = $this->filename($key)) && ($fp = fopen($file, 'r'))) {
            $expiration = (int) trim(fgets($fp));
            if($expiration === '0' || $expiration < time()){
                $data = '';
                while (!feof($fp)) {
                    $data .= fread($fp, 1024);
                }
                fclose($fp);
                return $this->unserialize($data);
            } else {
                fclose($fp);
            }
        }
        return $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        if ($fp = fopen($this->filename($key), 'w')) {
            if (flock($fp, LOCK_EX)) {
                $expiration = $ttl ? $ttl + time() : 0;
                fwrite($fp, "$expiration".PHP_EOL);
                fwrite($fp, $this->serialize($value));
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                return true;
            } else {
                fclose($fp);
            }
        }
        return false;
    }

    public function has($key)
    {
        if (is_file($file = $this->filename($key))) {
            if ($fp = fopen($file, 'r')) {
                $expiration = (int) trim(fgets($fp));
                fclose($fp);
                return $expiration === '0' || $expiration < time();
            }
        }
        return false;
    }
    
    public function delete($key)
    {
        return is_file($file = $this->filename($key)) && unlink($file);
    }
    
    public function clean()
    {
        if ($od = opendir($this->dir)) {
            while (($file = readdir($od)) !== false) {
                if (is_file($this->dir.$file)) {
                    unlink($this->dir.$file);
                }
            }
            closedir($ch);
            return true;
        }
        return false;
    }
    
    public function gc()
    {
        if ($od = opendir($this->dir)) {
            $maxtime = time() - $this->gc_maxlife;
            while (($item = readdir($od)) !== false) {
                if (is_file($file = $this->dir.$item) && $maxtime > filemtime($file)) {
                    unlink($file);
                }
            }
            closedir($ch);
        }
    }
    
    protected function filename($key)
    {
        return $this->dir.md5($key).$this->ext;
    }
}
