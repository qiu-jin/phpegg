<?php
namespace framework\core\http;

use framework\util\Str;
use framework\util\File;
use framework\core\Container;

class Uploaded
{
    private $file;
    private $check;
    private $validate;
    private static $error = [
        UPLOAD_ERR_INI_SIZE     => 'UPLOAD_ERR_INI_SIZE',
        UPLOAD_ERR_FORM_SIZE    => 'UPLOAD_ERR_FORM_SIZE',
        UPLOAD_ERR_PARTIAL      => 'UPLOAD_ERR_PARTIAL',
        UPLOAD_ERR_NO_FILE      => 'UPLOAD_ERR_NO_FILE',
        UPLOAD_ERR_NO_TMP_DIR   => 'UPLOAD_ERR_NO_TMP_DIR',
        UPLOAD_ERR_CANT_WRITE   => 'UPLOAD_ERR_CANT_WRITE'
    ];
    private static $image_type = [
         1 => 'GIF',  2 => 'JPG',  3 => 'PNG',   4 => 'SWF',
         5 => 'PSD',  6 => 'BMP',  7 => 'TIFF',  8 => 'TIFF',
         9 => 'JPC', 10 => 'JP2', 11 => 'JPX',  12 => 'JB2',
        13 => 'SWC', 14 => 'IFF', 15 => 'WBMP', 16 => 'XBM'
    ];
    
    public function __construct($file, $validate = null)
    {
        $this->file = $file;
        $this->validate = $validate;
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
        return isset($this->file['tmp_name']) ? File::mime($this->file['tmp_name']) : false;
    }
    
    public function image()
    {
        if (function_exists('exif_imagetype')) {
            $type = exif_imagetype($this->file['tmp_name']);
        } else {
            $type = getimagesize($this->file['tmp_name'])[2];
        }
        return $type && isset(self::$image_type[$type]) ? self::$image_type[$type] : false;
    }
    
    public function extension()
    {
        return isset($this->file['name']) ? pathinfo($this->file['name'], PATHINFO_EXTENSION) : false;
    }
    
    public function move($to)
    {
        if (!$this->check && !$this->check()) {
            return false;
        }
        if (stripos($to, '://')) {
            list($scheme, $uri) = explode('://', $to, 2);
            return Container::driver('storage', $scheme)->put($this->file['tmp_name'], $to);
        } else {
            return move_uploaded_file($this->file['tmp_name'], $to);
        }
    }
    
    public function check($validate = null)
    {
        $this->check = false;
        if ($this->file['error'] === UPLOAD_ERR_OK && is_uploaded_file($this->file['tmp_name'])) {
            if ($v = ($validate ?? $this->validate)) {
                if (isset($v['image']) && !in_array($this->image(), $v['image'], true)) {
                    return false;
                }
                if (isset($v['type']) && !in_array($this->type(), $v['type'], true)) {
                    return false;
                }
                if (isset($v['mime']) && !in_array($this->mime(), $v['mime'], true)) {
                    return false;
                }
                if (isset($v['extension']) && !in_array($this->extension(), $v['extension'], true)) {
                    return false;
                }
                if (isset($v['size'])) {
                    $size = $this->size();
                    if (is_array($v['size'])) {
                        if ($size < $v['size'][0] || $size > $v['size'][1]) {
                            return false;
                        }
                    } else {
                        if ($size > $v['size']) {
                            return false;
                        }
                    }
                }
            }
            return $this->check = true;
        }
        return false;
    }
    
    public function error()
    {
        return $this->file['error'] !== UPLOAD_ERR_OK ? self::$error[$this->file['error']] : null;
    }
}
