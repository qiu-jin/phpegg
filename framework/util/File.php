<?php
namespace framework\util;

use framework\core\Container;

class File extends \SplFileInfo
{
    public function getHash($hash = 'md5', $raw = false)
    {
        return hash_file($hash, $this->getPathname(), $raw);
    }
    
    public function getMime()
    {
        return self::mime($this->getPathname());
    }
    
    public function isImage($type = null)
    {
        return (new Image($this->getPathname(), true))->check($type);
    }
    
    public function getImage($type = null)
    {
        return ($image = Image::open($this->getPathname(), true)) && $image->check($type) ? $image : false;
    }

    public function uploadTo($to)
    {
        return self::upload($this->getPathname(), $to);
    }

    public static function mime($file, $is_buffer = false)
    {
        $finfo = finfo_open(FILEINFO_MIME); 
        $mime = $is_buffer ? finfo_buffer($finfo, $file) : finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mime;
    }
    
    public static function upload($from, $to, $is_buffer = false)
    {
        if (strpos($to, '://')) {
            list($scheme, $uri) = explode('://', $to, 2);
        }
        return Container::driver('storage', $scheme ?? null)->put($from, $uri ?? $to, $is_buffer);
    }
}
