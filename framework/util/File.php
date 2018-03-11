<?php
namespace framework\util;

use framework\core\Container;

class File extends \SplFileInfo
{   
    public function getMime()
    {
        return self::mime($this->getPathname());
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