<?php
namespace framework\util;

use framework\core\http\Response;

class Image
{
    // 图片信息
    private $info;
    // 图片资源
    private $image;
    
    /*
     * 打开图片
     */
    public static function open($path, array $check = null)
    {
        if (!$info = getimagesize($path)) {
            return false;
        }
        $type = image_type_to_extension($info[2], false);
        if ($check && !in_array($type, $check)) {
            return false;
        }
        if (function_exists($func = "imagecreatefrom$type") && is_resource($image = $func($path))) {
            return new self($image, [
                'path'      => $path,
                'type'      => $type,
                'mime'      => $info['mime'],
                'width'     => $info[0],
                'height'    => $info[1],
            ]);
        }
        return false;
    }
    
    /*
     * 创建空白画布
     */
    public static function canvas(int $width, int $height, $color = null, $type = 'png')
    {
        $image = imagecreate($width, $height);
        if ($color) {
            if (is_string($color)) {
                $color = self::parseStringColor($color);
            }
            imagecolorallocatealpha($image, ...$color);
        }
        return new self($image, [
            'type'      => $type,
            'mime'      => "image/$type",
            'width'     => $width,
            'height'    => $height,
        ]);
    }
    
    /*
     * 构造函数
     */
    private function __construct($image, $info)
    {
        $this->image = $image;
        $this->info  = $info;
    }
    
    /*
     * 信息
     */
    public function info($name = null)
    {
        return $name ? ($this->info[$name] ?? false) : ($this->info ?? false);
    }
    
    /*
     * 裁剪
     */
    public function crop(int $width, int $height, int $x = 0, int $y = 0, int $w = null, int $h = null)
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $color);
        imagecopyresampled($image, $$this->image, 0, 0, $x, $y, $width, $height, $w ?? $width, $h ?? $height);
        $this->info['width']  = $width;
        $this->info['height'] = $height;
        return $this->reset($image);
    }
    
    /*
     * 调整大小
     */
    public function resize(int $width, int $height, $zoom_in = false)
    {
        $w = $this->info['width'];
        $h = $this->info['height'];
        if ($w < $width && $h < $height && $zoom_in == false) {
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
    public function rotate(int $degrees = 90)
    {
        $image = imagerotate($$this->image, -$degrees, imagecolorallocatealpha($this->image, 0, 0, 0, 127));
        $this->info['width']  = imagesx($image);
        $this->info['height'] = imagesy($image);
        return $this->reset($image);
    }
    
    /*
     * 翻转
     */
    public function flip($flip_y = false)
    {
        $w = $this->info['width'];
        $h = $this->info['height'];
        $image1 = imagecreatetruecolor($w, $h);
        $image2 = $$this->image;
        if ($flip_y) {
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
    public function text(
       $text, $fontfile, $size, $color = '#000000',
       int $angle = 0, int $x = 1, int $y = 1, int $margin_x = 0, int $margin_y = 0
    ) {
        $info = imagettfbbox($size, $angle, $fontfile, $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $w = max($info[0], $info[2], $info[4], $info[6]) - $minx;
        $h = max($info[1], $info[3], $info[5], $info[7]) - $miny;
        if ($x > 0) {
            $x = $margin_x - $minx;
        } elseif ($x < 0) {
            $x = $this->info['width'] - $w - $minx - $margin_x;
        } else {
            $x = (($this->info['width'] - $w) / 2) - $minx;
        }
        if ($y > 0) {
            $y = $margin_y - $miny;
        } elseif ($y < 0) {
            $y = $this->info['height'] - $h - $miny - $margin_y;
        } else {
            $y = (($this->info['height'] - $h) / 2) - $miny;
        }
        if (is_string($color)) {
            $color = self::parseStringColor($color);
        }
        $col = imagecolorallocatealpha($$this->image, ...$color);
        imagettftext($this->image, $size, $angle, $x, $y, $col, $fontfile, $text);
        return $this;
    }
    
    /*
     * 水印
     */
    public function watermark($path, int $alpha = 100, int $x = 1, int $y = 1, int $margin_x = 0, int $margin_y = 0)
    {
        if (!($info = getimagesize($path))) {
            throw new \Exception("Illegal watermark file: $path");
        }
        if ($x > 0) {
            $x = $margin_x;
        } elseif ($x < 0) {
            $x = $this->info['width'] - $info[0] - $margin_x;
        } else {
            $x = ($this->info['width'] - $info[0]) / 2;
        }
        if ($y > 0) {
            $y = $margin_y;
        } elseif ($y < 0) {
            $y = $this->info['height'] - $info[1] - $margin_y;
        } else {
            $y = ($this->info['height'] - $info[1]) / 2;
        }
        $src   = imagecreatetruecolor($info[0], $info[1]);
        $color = imagecolorallocate($src, 255, 255, 255);
        $image = ('imagecreatefrom'.image_type_to_extension($info[2], false))($path);
        imagealphablending($image, true);
        imagefill($src, 0, 0, $color);
        imagecopy($src, $$this->image, 0, 0, $x, $y, $info[0], $info[1]);
        imagecopy($src, $image, 0, 0, 0, 0, $info[0], $info[1]);
        imagecopymerge($this->image, $src, $x, $y, 0, 0, $info[0], $info[1], $alpha);
        imagedestroy($src);
        imagedestroy($image);
        return $this;
    }
    
    /*
     * apply
     */
    public function apply(callable $handler)
    {
        if (is_resource($image = $handler($this->image))) {
            $this->reset($image);
        }
        return $this;
    }
    
    /*
     * 保存
     */
    public function save($path = null, $type = null, array $options = null)
    {
        if ($path == null) {
            if (empty($this->info['path'])) {
                throw \InvalidArgumentException('argument path not is null');
            }
            $path = $this->info['path'];
        }
        return $this->imageFunc($type)($$this->image, $path, ...$this->build($options));
    }
    
    /*
     * 数据
     */
    public function buffer($type = null, array $options = null)
    {
        ob_start();
        $ret = $this->imageFunc($type)($$this->image, null, ...$this->build($options));
        $data = ob_get_contents();
        ob_end_clean();
        return $ret === false ? false : $data;
    }
    
    /*
     * 输出
     */
    public function output($type = null, array $options = null)
    {
        if ($data = $this->buffer($type, $options)) {
            Response::send($data, $this->info['mime']);
        }
        throw new \Exception("Failed to output image");
    }
    
    /*
     * 上传
     */
    public function uploadTo($to, $type = null, array $options = null)
    {
        if ($data = $this->buffer($type, $options)) {
            return File::upload($data, $to, true);
        }
        throw new \Exception("Failed to upload image");
    }
    
    /*
     * reset
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
    private function build($options)
    {
        switch ($this->info['type']) {
            case 'jpeg':
                if (isset($options['interlace'])) {
                    imageinterlace($this->image, (int) $options['interlace']);
                }
                return isset($options['quality']) ? [(int) $options['quality']] : [];
            case 'png':
                imagesavealpha($this->image, true);
                $params = isset($options['quality']) ? [(int) $options['quality']] : [6];
                if (isset($options['filters'])) {
                    $params[] = (int) $options['filters'];
                }
                return $params;
            default:
                return [];
        }
    }

    /*
     * image create function
     */
    private function imageFunc($type)
    {
        if ($type == null) {
            if (empty($this->info['type'])) {
                throw \InvalidArgumentException('argument type not is null');
            }
            $type = $this->info['type'];
        } elseif (empty($this->info['type']) || $type != $this->info['type']) {
            $this->info['type'] = $type;
            $this->info['mime'] = "image/$type";
        }
        if (function_exists($func = "image$type")) {
            return $func;
        }
        throw new \Exception("Failed to create image");
    }
    
    /*
     * parse string color
     */
    public static function parseStringColor($color)
    {
        if (strpos($color, '#') == 0 && isset($color[6])) {
            $color = array_map('hexdec', str_split(substr($color, 1), 2));
            if (isset($color[2]) && (empty($color[3]) || $color[3] > 127)) {
                $color[3] = 0;
            }
            return $color;
        }
        throw new \Exception("Illegal color: $color");
    }
    
    public function __destruct()
    {
        empty($this->image) || imagedestroy($this->image);
    }
}
