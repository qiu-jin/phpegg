<?php
namespace framework\core\http;

use framework\util\Str;
use framework\util\Hash;
use framework\util\File;
use framework\util\Image;

class Uploaded
{
    // 文件信息
    private $file;
    // 图片实例
    private $image;
    // 是否验证
    private $is_valid;
    // 是否成功
    private $is_success;
    // 验证
    private $check;
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
     * 文件实例，如设置验证规则验证失败返回false
     */
    public static function file($name, $check = null)
    {
		$instance = new self(Request::file($name));
		if ($check) {
			$instance->check($check);
		}
		return $instance;
    }
	
    /*
     * 构造函数
     */
    public function __construct(array $file)
    {
        $this->file = $file;
		$this->is_valid = 
		$this->is_success = isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && 
			                isset($file['tmp_name']) && is_uploaded_file($file['tmp_name']);
    }
    
    /*
     * 是否成功
     */
    public function isSuccess()
    {
        return $this->is_success;
    }
    
    /*
     * 文件名
     */
    public function name()
    {
        return $this->file['name'] ?? null;
    }
    
    /*
     * 文件大小
     */
    public function size()
    {
        return $this->file['size'] ?? null;
    }
    
    /*
     * 文件类型
     */
    public function type()
    {
        return $this->file['type'] ?? null;
    }
    
    /*
     * 文件路径
     */
    public function path()
    {
        return $this->file['tmp_name'] ?? null;
    }
    
    /*
     * 文件扩展名
     */
    public function ext()
    {
		if (isset($this->file['name'])) {
			return $this->file['extension'] ?? $this->file['extension'] = File::ext($this->file['name']);
		}
    }
    
    /*
     * 文件mime
     */
    public function mime()
    {
		if (isset($this->file['tmp_name'])) {
			return $this->file['mime_type'] ?? $this->file['mime_type'] = File::mime($this->file['tmp_name']);
		}
    }
    
    /*
     * 图片实例，如非图片文件则返回false
     */
    public function image($check = null)
    {
		if ($this->is_valid) {
			return $this->image ?? $this->image = Image::open($this->path(), $check);
		}
    }
    
    /*
     * 保存文件
     */
    public function saveTo($dir)
    {
		$name = $this->randName();
		return $this->saveAs(Str::lastPad($dir, '/').$name) ? $name : false;
    }
	
    /*
     * 保存文件
     */
    public function saveAs($path)
    {
		return $this->is_valid && File::makeDir(dirname($path)) && move_uploaded_file($this->file['tmp_name'], $path);
    }
	
    /*
     * 上传到储存器
     */
    public function uploadTo($dir, $ext = null)
    {
		$name = $this->randName($ext);
        return $this->uploadAs(Str::lastPad($dir, '/').$name) ? $name : false;
    }
    
    /*
     * 上传到储存器
     */
    public function uploadAs($path)
    {
        return $this->is_valid && File::upload($this->file['tmp_name'], $path);
    }
    
    /*
     * 检查文件
     */
    public function check($check = null)
    {
        if (!$this->is_success) {
            return false;
        }
        if ($check) {
			if ((isset($check['ext']) && !in_array($this->ext(), $check['ext'])) ||
				(isset($check['type']) && !in_array($this->type(), $check['type'])) ||
				(isset($check['mime']) && !in_array($this->mime(), $check['mime'])) ||
				(!empty($check['image']) && !$this->image($check['image']))	
			) {
				return $this->is_valid = false;
			}
	        if (isset($check['size'])) {
	            $size = $this->size();
	            if (is_array($check['size'])) {
	                if ($size < $check['size'][0] || $size > $check['size'][1]) {
	                    return $this->is_valid = false;
	                }
	            } elseif ($size > $check['size']) {
	                return $this->is_valid = false;
	            }
	        }
        }
        return $this->is_valid = true;
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
	
    /*
     * 随机名
     */
    protected function randName($ext = null)
    {
		$name = Hash::hmac(Hash::random(16, true), $this->path());
		if (isset($ext)) {
			if ($ext === false) {
				return $name;
			}
			return "$name.$ext";
		}
		return $name.'.'.$this->ext();
	}
}
