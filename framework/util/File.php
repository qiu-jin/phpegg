<?php
namespace framework\util;

use framework\core\Container;

class File extends \SplFileInfo
{
    /*
     * 获取mime
     */
    public function getMime()
    {
        return self::mime($this->getPathname());
    }
    
    /*
     * 移动文件
     */
    public function moveTo($to)
    {
        return self::move($this->getPathname(), $to);
    }
    
    /*
     * 上传到storage实例
     */
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
    
    public static function move($from, $to)
    {
        return rename($from, substr($to, -1) == '/' ? $to.basename($from) : $to);
    }
    
    public static function upload($from, $to, $is_buffer = false)
    {
        if (strpos($to, '://')) {
            list($scheme, $uri) = explode('://', $to, 2);
        }
        return Container::driver('storage', $scheme ?? null)->put($from, $uri ?? $to, $is_buffer);
    }
}