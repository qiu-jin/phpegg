<?php
namespace framework\util;

use framework\core\Container;

class File
{
    /*
     * 文件读取
     */
    public static function get($file)
    {
        return is_file($file) ? file_get_contents($file) : false;
    }
    
    /*
     * 文件写入
     */
    public static function put($file, $contents, $flags = 0)
    {
        return self::makeDir(dirname($file)) && file_put_contents($file, $contents, $flags);
    }
    
    /*
     * 文件移动
     */
    public static function move($file, $to)
    {
        return is_file($file) && self::makeDir(dirname($to)) && rename($file, $to);
    }
    
    /*
     * 文件
     */
    public static function copy($file, $to)
    {
        return is_file($file) && self::makeDir(dirname($to)) && copy($file, $to);
    }
    
    /*
     * 文件复制
     */
    public static function delete($file)
    {
        return is_file($file) && unlink($file);
    }
    
    /*
     * 文件扩展名
     */
    public static function ext($file)
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }
    
    /*
     * 文件mime
     */
    public static function mime($file, $is_buffer = false)
    {
        $finfo = finfo_open(FILEINFO_MIME);
        $mime  = $is_buffer ? finfo_buffer($finfo, $file) : finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mime;
    }
    
    /*
     * 文件hash
     */
    public static function hash($file, $algo = 'md5', $raw = false)
    {
        return hash_file($algo, $file, $raw);
    }
    
    /*
     * 文件上传
     */
    public static function upload($file, $to, $is_buffer = false)
    {
        if (strpos($to, '://')) {
            list($scheme, $to) = explode('://', $to, 2);
        }
        return Container::driver('storage', $scheme ?? null)->put($file, $to, $is_buffer);
    }
    
    /*
     * 目录文件列表
     */
    public static function files($dir, $recursive = false)
    {
        $ret = [];
        $dir = Str::lastPad($dir, '/');
        if ($open = opendir($dir)) {
            while (($item = readdir($open)) !== false) {
                $file = $dir.$item;
                if (is_file($file)) {
                    $ret[] = $file;
                } elseif ($recursive && is_dir($file) && $item !== '.' && $item !== '..') {
                    $ret = array_merge($ret, self::files($file, $recursive));
                }
            }
            closedir($open);
        }
        return $ret;
    }
    
    /*
     * 目录是否可写
     */
    public static function isWritableDir($dir)
    {
        return is_dir($dir) && is_writable($dir);
    }
    
    /*
     * 如目录不存在则创建目录
     */
    public static function makeDir($dir, $mode = 0777, $recursive = true)
    {
        return is_dir($dir) || mkdir($dir, $mode, $recursive);
    }
    
    /*
     * 移动目录
     */
    public static function moveDir($dir, $to)
    {
        $dir = Str::lastPad($dir, '/');
        $to  = Str::lastPad($to, '/');
        if ($open = opendir($path)) {
            while (($item = readdir($open)) !== false) {
                if (is_dir($file = $dir.$item)) {
                    if ($item !== '.' && $item !== '..') {
                        self::moveDir($file, $to.$item);
                    }
                } else {
                    self::makeDir($to);
                    rename($file, $to.$item);
                }
            }
            closedir($open);
        }
        rmdir($dir);
    }
    
    /*
     * 删除目录
     */
    public static function deleteDir($dir)
    {
        self::cleanDir($dir);
        rmdir($dir);
    }
    
    /*
     * 清空目录（不删目录本身）
     * $fitler 返回true表示不删除对应文件
     */
    public static function cleanDir($dir, callable $fitler = null)
    {
        $dir = Str::lastPad($dir, '/');
        if ($open = opendir($dir)) {
            while (($item = readdir($open)) !== false) {
                if ($item !== '.' && $item !== '..' && is_dir($file = $dir.$item)) {
                    if (self::cleanDir($file, $fitler)) {
                        rmdir($file);
                    } else {
                        $empty = false;
                    }
                } else {
                    if ($fitler === null || $fitler($file) !== true) {
                        unlink($file);
                    } else {
                        $empty = false;
                    }
                }
            }
            closedir($open);
        }
        return $empty ?? true;
    }
}
