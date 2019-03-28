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
    // 是否有效
    private $is_valid;
    // 是否成功
    private $is_success;
    // 错误信息
    private static $error = [
        UPLOAD_ERR_OK           => 'UPLOAD_ERR_OK',
        UPLOAD_ERR_INI_SIZE     => 'UPLOAD_ERR_INI_SIZE',
        UPLOAD_ERR_FORM_SIZE    => 'UPLOAD_ERR_FORM_SIZE',
        UPLOAD_ERR_PARTIAL      => 'UPLOAD_ERR_PARTIAL',
        UPLOAD_ERR_NO_FILE      => 'UPLOAD_ERR_NO_FILE',
        UPLOAD_ERR_NO_TMP_DIR   => 'UPLOAD_ERR_NO_TMP_DIR',
        UPLOAD_ERR_CANT_WRITE   => 'UPLOAD_ERR_CANT_WRITE'
    ];
    
    /*
     * 构造函数
     */
    public function __construct(array $file)
    {
        $this->file = $file;
        $this->is_valid = isset($file['tmp_name']) && is_uploaded_file($file['tmp_name']);
        $this->is_success = isset($file['error']) && $file['error'] === UPLOAD_ERR_OK;
    }

    /*
     * 文件实例，如设置验证规则验证失败返回false
     */
    public static function file($name, $check = null)
    {
		if ($file = Request::file($name)) {
			$uploaded = new self($file);
			return (!$check || $uploaded->check($check)) ? $uploaded : false;
		}
    }
    
    /*
     * 文件名
     */
    public function name()
    {
        return $this->file['name'] ?? false;
    }
    
    /*
     * 文件大小
     */
    public function size()
    {
        return $this->file['size'] ?? false;
    }
    
    /*
     * 文件类型
     */
    public function type()
    {
        return $this->file['type'] ?? false;
    }
    
    /*
     * 文件路径
     */
    public function path()
    {
        return $this->file['tmp_name'] ?? false;
    }
    
    /*
     * 文件扩展名
     */
    public function ext()
    {
        return $this->file['extension'] ?? $this->file['extension'] = File::ext($this->name());
    }
    
    /*
     * 文件mime
     */
    public function mime()
    {
        return $this->file['mime_type'] ?? $this->file['mime_type'] = $this->check() && File::mime($this->path());
    }
    
    /*
     * 图片实例，如非图片文件则返回false
     */
    public function image($check = null)
    {
        return $this->image ?? $this->image = $this->check() && Image::open($this->path(), $check);
    }
    
    /*
     * 移动文件
     */
    public function move($to)
    {
        return $this->check() && File::makeDir(dirname($to), 0777, true) && move_uploaded_file($this->path(), $to);
    }
    
    /*
     * 上传到储存器
     */
    public function uploadTo($to)
    {
        return $this->check() && File::upload($this->file['tmp_name'], $to);
    }
    
    /*
     * 是否有效
     */
    public function isValid()
    {
        return $this->is_valid;
    }
    
    /*
     * 是否成功
     */
    public function isSuccess()
    {
        return $this->is_success;
    }
    
    /*
     * 检查文件
     */
    public function check($check = true)
    {
        if (!$this->is_success || !$this->is_valid) {
            return false;
        }
        if ($check === true) {
            return true;
        }
        if (isset($check['ext']) && !in_array($this->ext(), $check['ext'])) {
            return false;
        }
        if (isset($check['type']) && !in_array($this->type(), $check['type'])) {
            return false;
        }
        if (isset($check['mime']) && !in_array($this->mime(), $check['mime'])) {
            return false;
        }
        if (!empty($check['image']) && !$this->image($check['image'])) {
            return false;
        }
        if (isset($check['size'])) {
            $size = $this->size();
            if (is_array($check['size'])) {
                if ($size < $check['size'][0] || $size > $check['size'][1]) {
                    return false;
                }
            } elseif ($size > $check['size']) {
                return false;
            }
        }
        return true;
    }
    
    /*
     * 获取错误信息
     */
    public function errno()
    {
        return $this->file['error'] ?? null;
    }
    
    public function error()
    {
        return self::$error[$this->file['error']] ?? null;
    }
}
