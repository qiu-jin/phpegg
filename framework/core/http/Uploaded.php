<?php
namespace framework\core\http;

use framework\core\Model;

class Uploaded
{
    private $file;
    
    public function __construct($name, $filter = null)
    {
        $this->file = isset($_FILES[$name]) ? $_FILES[$name] : null;
    }
    
    public function get($name = null)
    {
        return $name ? $this->file : $this->file[$name] ?? null;
    }
    
    public function move($to)
    {
        if (stripos($to, '://')) {
            list($scheme, $uri) = explode('://', $to, 2);
            return Model::connect('storage', $scheme)->put($this->file['tmp_name'], $to);
        }
        return move_uploaded_file($this->file['tmp_name'], $to);
    }
    
    public function filter($filter)
    {
        return isset($this->file) && $this->file['error'] === UPLOAD_ERR_OK && is_uploaded_file($this->file['tmp_name']);
    }
}
