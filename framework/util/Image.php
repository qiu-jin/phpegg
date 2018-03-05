<?php
namespace framework\util;

class Image
{
    private $path;
    private $info;
    private $source;
    
	public function __construct($path)
	{
        if (is_file($path)) {
            $this->path = $path;
        } else {
            throw new \Exception("Illegal file: $path");
        }
	}
    
    /*
     * 保存
     */
    public function info($name = null)
    {
        if (!isset($this->info)) {
            if ($info = getimagesize($this->path)) {
                $this->info = [
                    'type'      => image_type_to_extension( $info[2], false),
                    'mime'      => $info['mime'],
                    'width'     => $info[0],
                    'height'    => $info[1],
                ];
            }
            throw new \Exception("Illegal image file: $this->path");
        }
        return $name ? $this->info : ($this->info[$name] ?? false);
    }
    
    /*
     * 裁剪
     */
    public function crop()
    {
        return $this;
    }
    
    /*
     * 调整大小
     */
    public function resize()
    {
        return $this;
    }
    
    /*
     * 旋转
     */
    public function rotate($degrees = 90)
    {
        $source = imagerotate($this->source(), -$degrees, imagecolorallocatealpha($this->source, 0, 0, 0, 127));
        $this->info['width']  = imagesx($source);
        $this->info['height'] = imagesy($source;
        return $this->resetSource($source);
    }
    
    /*
     * 翻转
     */
    public function flip($direction)
    {
        $w = $this->info('width');
        $h = $this->info['height'];
        $s1 = imagecreatetruecolor($w, $h);
        $s2 = $this->source();
        if ($direction == 1) {
            for ($y = 0; $y < $h; $y++) {
                imagecopy($s1, $s2, 0, $h - $y - 1, 0, $y, $w, 1);
            }
        } elseif ($direction == 2) {
            for ($x = 0; $x < $w; $x++) {
                imagecopy($s1, $s2, $w - $x - 1, 0, $x, 0, 1, $h);
            }
        } else {
            throw new \Exception('Failed to flip image');
        }
        return $this->resetSource($s2);
    }
    
    /*
     * 文字
     */
    public function text()
    {
        return $this;
    }
    
    /*
     * 水印
     */
    public function watermark()
    {
        return $this;
    }
    
    /*
     * 保存
     */
    public function save($path = null, int $quality = 90)
    {
        if ($path === null) {
            $path = $this->path;
        }
    }
    
    /*
     * source
     */
    public function source()
    {
        if (isset($this->source)) {
            return $this->source;
        }
        if (function_exists($func = 'imagecreatefrom'.$this->info('type'))
            && is_resource($source = $func($this->path))
        ) {
            return $this->source = $source;
        }
        throw new \Exception("Failed to create image resource");
    }
    
    /*
     * source
     */
    private function resetSource($source)
    {
        imagedestroy($this->source);
        $this->source = $source;
        return $this;
    }
}