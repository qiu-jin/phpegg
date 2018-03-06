<?php
namespace framework\util;

use framework\core\http\Response;

class Image
{
    private $path;
    private $info;
    private $image;
    
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
            if (!($info = getimagesize($this->path))) {
                throw new \Exception("Illegal image file: $this->path");
            }
            $this->info = [
                'type'      => image_type_to_extension( $info[2], false),
                'mime'      => $info['mime'],
                'width'     => $info[0],
                'height'    => $info[1],
            ];
        }
        return $name ? ($this->info[$name] ?? false) : $this->info;
    }
    
    /*
     * 裁剪
     */
    public function crop(int $width, int $height, $x = 0, $y = 0, $w = null, $h = null)
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $color);
        imagecopyresampled($image, $this->resource(), 0, 0, $x, $y, $width, $height, $w ?? $width, $h ?? $height);
        $this->info['width']  = $width;
        $this->info['height'] = $height;
        return $this->reset($image);
    }
    
    /*
     * 调整大小
     */
    public function resize(int $width, int $height)
    {
        $w = $this->info('width');
        $h = $this->info['height'];
        if ($w <= $width && $h <= $height) {
            return $this;
        }
        $scale = min($width / $w, $height / $h);
        $width  = $w * $scale;
        $height = $h * $scale;
        return $this->crop($width, $height, 0, 0, $w, $h);
    }
    
    /*
     * 旋转
     */
    public function rotate($degrees = 90)
    {
        $image = imagerotate($this->resource(), -$degrees, imagecolorallocatealpha($this->image, 0, 0, 0, 127));
        $this->info['width']  = imagesx($image);
        $this->info['height'] = imagesy($image);
        return $this->reset($image);
    }
    
    /*
     * 翻转
     */
    public function flip($direction_flip_y = false)
    {
        $w = $this->info('width');
        $h = $this->info['height'];
        $image1 = imagecreatetruecolor($w, $h);
        $image2 = $this->resource();
        if ($direction_flip_y) {
            for ($x = 0; $x < $w; $x++) {
                imagecopy($image1, $image2, $w - $x - 1, 0, $x, 0, 1, $h);
            }
        } else {
            for ($y = 0; $y < $h; $y++) {
                imagecopy($image1, $image2, 0, $h - $y - 1, 0, $y, $w, 1);
            }
        }
        return $this->reset($image1);
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
    public function watermark($source, int $x = -1, int $y = -1, int $alpha = 100)
    {
        return $this;
    }
    
    /*
     * source
     */
    public function resource()
    {
        if (isset($this->image)) {
            return $this->image;
        }
        if (function_exists($func = 'imagecreatefrom'.$this->info('type'))
            && is_resource($image = $func($this->path))
        ) {
            return $this->image = $image;
        }
        throw new \Exception("Failed to create image resource");
    }
    
    /*
     * 保存
     */
    public function save($path = null, $type = null, int $quality = 90)
    {
        return $this->imageFunc($type)($this->resource(), $path ?? $this->path, ...$this->build($type, $quality));
    }
    
    /*
     * 数据
     */
    public function buffer($type = null, int $quality = 90)
    {
        ob_start();
        $ret = $this->imageFunc($type)($this->resource(), ...$this->build($type, $quality));
        $data = ob_get_contents();
        ob_end_clean();
        return $ret === false ? false : $data;
    }
    
    /*
     * 输出
     */
    public function output($type = null, $quality = 90)
    {
        if ($data = $this->buffer($type, $quality)) {
            Response::send($data, $this->info('mime'));
        }
        throw new \Exception("Failed to output image");
    }
    
    /*
     * source
     */
    private function reset($image)
    {
        imagedestroy($this->image);
        $this->image = $image;
        return $this;
    }
    
    /*
     * build
     */
    private function build($type, $quality)
    {
        $params = [];
        switch ($type) {
            case 'jpg':
            case 'jpeg':
                $params[] = $quality;
                break;
            case 'png':
                imagesavealpha($this->image, true);
                $params[] = min((int) ($quality / 10), 9);
                break;
        }
        return $params;
    }

    /*
     * imageFunc
     */
    private function imageFunc($type)
    {
        if ($type == null) {
            $type = $this->info('type');
        }
        if (function_exists($func = "image$type")) {
            return $func;
        }
        throw new \Exception("Failed to create image");
    }
    
    public function __destruct()
    {
        empty($this->image) || imagedestroy($this->image);
    }
}