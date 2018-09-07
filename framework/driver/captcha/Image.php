<?php
namespace framework\driver\captcha;

use framework\util\Arr;
use framework\util\Image as Img;
use framework\core\http\Session;
use framework\core\http\Request;

/*
 * <input name='$this->name'></input><image src='$this->src' />
 */
class Image
{
    // 验证码图片地址
    protected $src;
    // 表单验证码字段名
    protected $name;
    // 验证码文字
    protected $text;
    // 默认验证码图片样式
    protected $style = [
        // 图片宽度
        'width'     => 150,
        // 图片高度
        'height'    => 35,
        // 文字个数
        'length'    => 4,
        /*
        // 文字集
        'characters'    => '',
        // 文字字体集合数组
        'font_files'    => [],
        // 文字颜色集合数组
        'font_colors'   => [],
        */
        // 文字倾斜最大角度
        'font_angle'    => 15,
        // 文字伸缩区间
        'font_size_rand'    => [-20, -10],
        // 文字间隔区间
        'font_padding_rand' => [1, 3],
        // 画线数区间
        'line_count_rand'   => [2, 5],
        // 画线粗细区间
        'line_thick_rand'   => [2, 5],
        // 图片背景色
        //'background_colors'  => [],
        // 背景图片集合数组
        //'background_images'  => [],
    ];
    protected $enable_session;

    public function __construct($config)
    {
        $this->src = $config['src'];
        $this->name = $config['name'] ?? 'image-captcha';
        if (isset($config['style'])) {
            $this->style = $config['style'] + $this->style;
        }
        $this->enable_session = $config['enable_session'] ?? true;
    }
    
    public function src()
    {
        return $this->src;
    }
    
    public function name()
    {
        return $this->name;
    }
    
    public function text()
    {
        return $this->text;
    }
    
    public function value($clean = true)
    {
        $v = Session::get($this->name);
        $clean && $this->clean();
        return $v;
    }
    
    public function clean()
    {
        Session::delete($this->name);
    }

    public function verify($value = null, $clean = true)
    {
        $v = $this->value($clean);
        return $v !== null && $v == $value ?? Request::post($this->name);
    }
    
    public function output(array $style = null)
    {
        $this->build($style)->output();
    }
    
    public function buffer(array $style = null)
    {
        return $this->build($style)->buffer();
    }
    
    public function encode(array $style = null)
    {
        $image = $this->build($style);
        return 'data:image/'.$image->info('type').';base64,'.base64_encode($image->buffer());
    }
    
    protected function build($style)
    {
        $style = $style ? $style + $this->style : $this->style;
        // 创建
        if (empty($style['background_images'])) {
            $color = empty($style['background_colors']) 
                   ? [rand(0, 255), rand(0, 255), rand(0, 255), 0]
                   : Arr::rand($style['background_colors']);
            $image = Img::blank($style['width'], $style['height'], $color);
        } else {
            $image = Img::open(Arr::rand($style['background_images']))->resize($style['width'], $style['height']);
        }
        // 文字
        $l = mb_strlen($style['characters']) - 1;
        $this->text = '';
        $padding = rand(...$style['font_padding_rand']);
        for ($i = 0; $i < $style['length']; $i++) {
            $char = mb_substr($style['characters'], rand(0, $l), 1);
            $this->text .= $char;
            $size = $style['height'] + rand(...$style['font_size_rand']);
            $angle = rand(-1 * $style['font_angle'], $style['font_angle']);
            $margin = $padding + ($i * ($style['width'] - $padding) / $style['length']);
            $image->text($char, Arr::rand($style['font_files']), $size, $this->fontColor($style), $angle, 1, 0, $margin);
        }
        // 画线
        $image->apply(function ($im) use ($style) {
            for ($i = rand(...$style['line_count_rand']); $i > 0; $i--) {
                $color = $this->fontColor($style);
                if (is_string($color)) {
                    $color = Img::parseStringColor($color);
                }
                imagesetthickness($im, rand(...$style['line_thick_rand']));
                imageline(
                    $im,
                    rand(0, $style['width'] + $i * rand(0, $style['height'])),
                    rand(0, $style['height']),
                    rand(0, $style['width']),
                    rand(0, $style['height']),
                    imagecolorallocate($im, $color[0], $color[1], $color[2])
                );
            }
        });
        if ($this->enable_session) {
            Session::set($this->name, $this->text);
        }
        return $image;
    }
    
    protected function fontColor($style)
    {
        if (empty($style['font_colors'])) {
            return [rand(0, 255), rand(0, 255), rand(0, 255), 0];
        } else {
            return Arr::rand($style['font_colors']);
        }
    }
}
