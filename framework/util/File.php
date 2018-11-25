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
        return is_file($file) && file_get_contents($file);
    }
    
    /*
     * 文件写入
     */
    public static function put($file, $contents, $lock = false)
    {
        return self::makeDir(dirname($file), 0777, true) &&
               file_put_contents($file, $contents, $lock ? LOCK_EX : 0);
    }
    
    /*
     * 文件追加
     */
    public static function append($file, $contents, $lock = false)
    {
        return self::makeDir(dirname($file), 0777, true) && 
               file_put_contents($file, $contents, ($lock ? LOCK_EX : 0) | FILE_APPEND);
    }
    
    /*
     * 文件移动
     */
    public static function move($file, $to)
    {
        return is_file($file) && self::makeDir(dirname($to), 0777, true) && rename($file, $to);
    }
    
    /*
     * 文件
     */
    public static function copy($file, $to)
    {
        return is_file($file) && self::makeDir(dirname($to), 0777, true) && copy($file, $to);
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
     * 是否为图片文件
     */
    public static function isImage($file, $type = null)
    {
        return ($image = Image::open($file)) && ($type === null || $image->check($type));
    }
    
    /*
     * 文件上传
     */
    public static function upload($file, $to, $is_buffer = false)
    {
        if (strpos($to, '://')) {
            list($scheme, $uri) = explode('://', $to, 2);
        }
        return Container::driver('storage', $scheme ?? null)->put($from, $uri ?? $to, $is_buffer);
    }
    
    /*
     * 目录文件列表
     */
    public static function files($path, $recursive = false)
    {
        $ret = [];
        $path = Str::tailPad($path);
        if ($open = opendir($path)) {
            while (($item = readdir($open)) !== false) {
                $file = $path.$item;
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
    public static function isWritableDir($path)
    {
        return is_dir($path) && is_writable($path);
    }
    
    /*
     * 如目录不存在则创建目录
     */
    public static function makeDir($path, $mode = 0777, $recursive = false)
    {
        return is_dir($path) || mkdir($path, $mode, $recursive);
    }
    
    /*
     * 移动目录
     */
    public static function moveDir($path, $to)
    {
        $path = Str::tailPad($path);
        $to   = Str::tailPad($to);
        if ($open = opendir($path)) {
            while (($item = readdir($open)) !== false) {
                if (is_dir($file = $path.$item)) {
                    if ($item !== '.' && $item !== '..') {
                        self::moveDir($file, $to.$item);
                    }
                } else {
                    self::makeDir($to, 0777, true);
                    rename($file, $to.$item);
                }
            }
            closedir($open);
        }
        rmdir($path);
    }
    
    /*
     * 删除目录
     */
    public static function deleteDir($path)
    {
        self::cleanDir($path);
        rmdir($path);
    }
    
    /*
     * 清空目录
     * $fitler 返回true表示不删除
     */
    public static function cleanDir($path, callable $fitler = null)
    {
        $path = Str::tailPad($path);
        if ($open = opendir($path)) {
            while (($item = readdir($open)) !== false) {
                if (is_dir($file = $path.$item)) {
                    if ($item !== '.' && $item !== '..') {
                        if (self::cleanDir($file, $fitler)) {
                            rmdir($file);
                        } else {
                            $empty = false;
                        }
                    }
                } else {
                    if ($fitler === null || !$fitler($file)) {
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
