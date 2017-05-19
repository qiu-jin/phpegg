<?php
namespace framework\core\http;

use framework\util\File;
use framework\core\Model;

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
    
    public function __construct($file, $validate = null)
    {
        $this->file = $file;
        $validate && $this->validate = $validate;
    }
    
    public function name()
    {
        return isset($this->file['name']) ? $this->file['name'] : false;
    }
    
    public function size()
    {
        return isset($this->file['size']) ? $this->file['size'] : false;
    }
    
    public function type()
    {
        return isset($this->file['type']) ? $this->file['type'] : false;
    }
    
    public function mime()
    {
        return isset($this->file['tmp_name']) ? File::mime($this->file['tmp_name']) : false;
    }
    
    public function isImage()
    {
        if (function_exists('exif_imagetype')) {
            $type = exif_imagetype($this->file['tmp_name']);
        } else {
            $type = getimagesize($this->file['tmp_name'])[2];
        }
        return $type && in_array($type, [1, 2, 3], true);
    }
    
    public function extension()
    {
        return isset($this->file['name']) ? pathinfo($this->file['name'], PATHINFO_EXTENSION) : false;
    }
    
    public function move($to)
    {
        isset($this->check) || $this->check();
        if ($this->check) {
            if (stripos($to, '://')) {
                list($scheme, $uri) = explode('://', $to, 2);
                return Model::connect('storage', $scheme)->put($this->file['tmp_name'], $to);
            }
            return move_uploaded_file($this->file['tmp_name'], $to);
        }
        return false;
    }
    
    public function save($dir, $is_raw_name = false)
    {
        $name = $is_raw_name ? $this->name() : md5(uniqid()).'.'.$this->extension();
        return $this->move($dir.'/'.$name) ? $name : false;
    }
    
    public function check($validate = null)
    {
        $this->check = false;
        if ($this->file['error'] === UPLOAD_ERR_OK && is_uploaded_file($this->file['tmp_name'])) {
            if (!$validate && $this->validate) {
                $validate = $this->validate;
            }
            if ($validate) {
                if (isset($validate['type']) && !in_array($this->type(), $validate['type'], true)) {
                    return false;
                }
                if (isset($validate['mime']) && !in_array($this->mime(), $validate['mime'], true)) {
                    return false;
                }
                if (isset($validate['extension']) && !in_array($this->extension(), $validate['extension'], true)) {
                    return false;
                }
                if (isset($validate['isimage']) && !$this->isImage()) {
                    return false;
                }
                if (isset($validate['size'])) {
                    if (is_array($validate['size'])) {
                        $size = $this->size();
                        if ($size < $validate['size'][0] || $size > $validate['size'][1]) {
                            return false;
                        }
                    } else {
                        if ($this->size() > $validate['size']) {
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
        if ($this->file['error'] !== UPLOAD_ERR_OK) {
            if (isset(self::$error[$this->file['error']])) {
                return self::$error[$this->file['error']];
            }
            return 'UPLOAD_ERR_UNKNOWN';
        }
        return null;
    }
}
