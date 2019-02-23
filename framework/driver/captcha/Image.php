<?php
namespace framework\driver\captcha;

use framework\util\Arr;
use framework\util\Image as Img;
use framework\core\http\Request;
use framework\core\http\Session;

class Image
{
    // 验证码图片地址
    protected $src;
    // 表单验证码字段名
    protected $name = 'image-captcha';
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

    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->src = $config['src'];
		if (isset($config['name'])) {
			$this->name = $config['name'];
		}
        if (isset($config['style'])) {
            $this->style = $config['style'] + $this->style;
        }
    }
    
    /*
     * 获取验证码图片地址
     */
    public function src()
    {
        return $this->src;
    }
    
    /*
     * 获取验证表单字段名
     */
    public function name()
    {
        return $this->name;
    }
    
    /*
     * 获取验证文字
     */
    public function text()
    {
        return $this->text;
    }
	
    /*
     * 输出验证码图片
     */
    public function output(array $style = null)
    {
        $this->build($style)->output();
    }
    
    /*
     * 获取验证码图片数据
     */
    public function buffer(array $style = null)
    {
        return $this->build($style)->buffer();
    }
    
    /*
     * 编码验证码图片
     */
    public function encode(array $style = null)
    {
        $image = $this->build($style);
        return 'data:image/'.$image->info('type').';base64,'.base64_encode($image->buffer());
    }
	
    /*
     * 验证
     */
    public function verify($value = null)
    {
		return ($v = Request::post($this->name)) !== null && $v === ($value ?? Session::pull($this->name));
    }
    
    /*
     * 生成验证码图片
     */
    protected function build($style)
    {
        $style = $style ? $style + $this->style : $this->style;
        // 创建
        if (empty($style['background_images'])) {
            $color = empty($style['background_colors']) 
                   ? [rand(0, 255), rand(0, 255), rand(0, 255), 0]
                   : Arr::random($style['background_colors']);
            $image = Img::blank($style['width'], $style['height'], $color);
        } else {
            $image = Img::open(Arr::random($style['background_images']))->resize($style['width'], $style['height']);
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
            $image->text($char, Arr::random($style['font_files']), $size, $this->fontColor($style), $angle, 1, 0, $margin);
        }
        // 干扰线
        $image->apply(function ($im) use ($style) {
            for ($i = rand(...$style['line_count_rand']); $i > 0; $i--) {
                $color = $this->randomFontColor($style);
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
        return $image;
    }
    
    /*
     * 随机字体颜色
     */
    protected function randomFontColor($style)
    {
        if (empty($style['font_colors'])) {
            return [rand(0, 255), rand(0, 255), rand(0, 255), 0];
        } else {
            return Arr::random($style['font_colors']);
        }
    }
}
