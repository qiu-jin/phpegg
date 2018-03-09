<?php
namespace framework\core\http;

use framework\util\File;
use framework\util\Image;

class Uploaded
{
    private $file;
    private $image;
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
        return $this->file['mime_type'] ??
               $this->file['mime_type'] = isset($this->file['tmp_name']) ? File::mime($this->file['tmp_name']) : false;
    }
    
    public function image()
    {
        return $this->image ??
               $this->image = isset($this->file['tmp_name']) ? new Image($this->file['tmp_name'], true) : false;
    }
    
    public function ext()
    {
        return isset($this->file['name']) ? pathinfo($this->file['name'], PATHINFO_EXTENSION) : false;
    }
    
    public function move($to)
    {
        if (!$this->check && !$this->check()) {
            return false;
        }
        return move_uploaded_file($this->file['tmp_name'], $to);
    }
    
    public function uploadTo($to)
    {
        if (!$this->check && !$this->check()) {
            return false;
        }
        return File::upload($this->file['tmp_name'], $to);
    }
    
    public function check($validate = null)
    {
        $this->check = false;
        if ($this->file['error'] === UPLOAD_ERR_OK && is_uploaded_file($this->file['tmp_name'])) {
            if ($v = ($validate ?? $this->validate)) {
                if (isset($v['ext']) && !in_array($this->ext(), $v['ext'])) {
                    return false;
                }
                if (isset($v['type']) && !in_array($this->type(), $v['type'])) {
                    return false;
                }
                if (isset($v['mime']) && !in_array($this->mime(), $v['mime'])) {
                    return false;
                }
                if (isset($v['image']) && $this->image()->check($v['image'])) {
                    return false;
                }
                if (isset($v['size'])) {
                    $size = $this->size();
                    if (is_array($v['size'])) {
                        if ($size < $v['size'][0] || $size > $v['size'][1]) {
                            return false;
                        }
                    } elseif ($size > $v['size']) {
                        return false;
                    }
                }
            }
            return $this->check = true;
        }
        return false;
    }
    
    public function error()
    {
        return $this->file['error'] != UPLOAD_ERR_OK ? [$this->file['error'], self::$error[$this->file['error']]] : null;
    }
}
