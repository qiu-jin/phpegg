<?php
namespace framework\core\http;

use framework\util\File;
use framework\util\Image;

class Uploaded
{
    // 文件信息
    private $file;
    // 图片实例
    private $image;
    // 图片检查
    private $checked;
    // 错误信息
    private static $error = [
        UPLOAD_ERR_INI_SIZE     => 'UPLOAD_ERR_INI_SIZE',
        UPLOAD_ERR_FORM_SIZE    => 'UPLOAD_ERR_FORM_SIZE',
        UPLOAD_ERR_PARTIAL      => 'UPLOAD_ERR_PARTIAL',
        UPLOAD_ERR_NO_FILE      => 'UPLOAD_ERR_NO_FILE',
        UPLOAD_ERR_NO_TMP_DIR   => 'UPLOAD_ERR_NO_TMP_DIR',
        UPLOAD_ERR_CANT_WRITE   => 'UPLOAD_ERR_CANT_WRITE'
    ];
    
    public function __construct(array $file)
    {
        $this->file = $file;
    }
    
    public static function file(array $file, array $validate = null)
    {
        $uploaded = new self($file);
        return $validate && !$uploaded->check($validate) ? false : $uploaded;
    }
    
    public function name()
    {
        return $this->file['name'] ?? false;
    }
    
    public function size()
    {
        return $this->file['size'] ?? false;
    }
    
    public function type()
    {
        return $this->file['type'] ?? false;
    }
    
    public function path()
    {
        return $this->file['tmp_name'] ?? false;
    }
    
    public function mime()
    {
        return $this->file['mime_type'] ??
               $this->file['mime_type'] = isset($this->file['tmp_name']) ? File::mime($this->file['tmp_name']) : false;
    }
    
    public function image()
    {
        return $this->image ??
               $this->image = isset($this->file['tmp_name']) ? Image::open($this->file['tmp_name'], true) : false;
    }
    
    public function ext()
    {
        return isset($this->file['name']) ? File::ext($this->file['name']) : false;
    }
    
    public function move($to)
    {
        return move_uploaded_file($this->file['tmp_name'], $to);
    }
    
    public function uploadTo($to)
    {
        return File::upload($this->file['tmp_name'], $to);
    }
    
    public function isSuccess()
    {
        return $this->file['error'] === UPLOAD_ERR_OK && is_uploaded_file($this->file['tmp_name']);
    }
    
    public function check(array $validate)
    {
        if ($this->isSuccess()) {
            if (isset($validate['ext']) && !in_array($this->ext(), $validate['ext'])) {
                return false;
            }
            if (isset($validate['type']) && !in_array($this->type(), $validate['type'])) {
                return false;
            }
            if (isset($validate['mime']) && !in_array($this->mime(), $validate['mime'])) {
                return false;
            }
            if (isset($validate['image']) && $this->image() && $this->image->check($validate['image'])) {
                return false;
            }
            if (isset($validate['size'])) {
                $size = $this->size();
                if (is_array($validate['size'])) {
                    if ($size < $validate['size'][0] || $size > $validate['size'][1]) {
                        return false;
                    }
                } elseif ($size > $validate['size']) {
                    return false;
                }
            }
        }
        return false;
    }
    
    public function error()
    {
        return $this->file['error'] != UPLOAD_ERR_OK ? [$this->file['error'], self::$error[$this->file['error']]] : null;
    }
}
