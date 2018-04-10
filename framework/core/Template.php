<?php
namespace framework\core;

use framework\util\Arr;
use framework\core\http\Request;
use framework\core\exception\TemplateException;

class Template
{
    protected static $init;
    // 
    protected static $operators;
    // 配置
    protected static $config    = [
        // 空标签
        'blank_tag'             => 'php',
        // 块标签
        'block_tag'             => 'block',
        // 原生标签
        'verbatim_tag'          => 'verbatim',
        // 引用标签
        'include_tag'           => 'include',
        // 继承标签
        'extends_tag'           => 'extends',
        // 结构语句前缀符
        'struct_attr_prefix'    => '@',
        // 赋值语句前缀符
        'assign_attr_prefix'    => '$',
        // 参数语句前缀符
        'arg_attr_prefix'       => ':',
        // 文本插入左右边界符号
        'text_border_sign'      => ['{{', '}}'],
        // 文本插入是否自动转义
        'auto_escape_text'      => true,
        // 文本转义符号与反转义符号
        'text_escape_sign'      => ['$', '!'],
        // 是否支持原生PHP函数
        'enable_native_func'    => false,
        // 内置变量标识符
        'template_var_sign'     => '$',
        // 原样输出文本标识符（不解析文本插入边界符以其内内容）
        'verbatim_text_sign'    => '!',
        // include方法名
        'view_include_method'   => View::class.'::path',
        // extends方法名
        'view_extends_method'   => View::class.'::extends',
        // model方法名
        'view_model_method'     => View::class.'::callModel',
        // filter方法名
        'view_filter_method'    => View::class.'::callFilter',
    ];
    
    // 内置变量
    protected static $vars    = [
        'GET'           => '_GET',
        'POST'          => '_POST',
        'COOKIE'        => '_COOKIE',
        'SESSION'       => '_SESSION',
    ];
    
    // 内置函数
    protected static $functions = [
        // 类型判断
        'is'            => '("is_$1")($0)',
        // 是否存在
        'has'           => 'isset($0)',
        // 是否为空
        'empty'         => 'empty($0)',
        // 默认值
        'default'       => '$0 ?? $1',
        // 转为字符串
        'str'           => 'strval($0)',
        // 转为数字
        'num'           => '($0+0)',
        // 字符串拼接
        'concat'        => '$0.$1',
        // 字符串拼接
        'format'        => 'sprintf($0, $1, ...)',
        // 字符串替换
        'replace'       => 'str_replace($1, $2, $0)',
        // 字符串截取
        'substr'        => 'substr($0, $1, $2)',
        // 字符串重复
        'repeat'        => 'str_repeat($0, $1)',
        // 字符串补全填充
        'pad'           => 'str_pad($0, $1, ...)',
        // 字符串长度
        'length'        => 'strlen($0)',
        // 字符串大写
        'lower'         => 'strtolower($0)',
        // 字符串小写
        'upper'         => 'strtoupper($0)',
        // 字符串首字母大写
        'ucfirst'       => 'ucfirst($0)',
        // 每个单词的首字母大写
        'ucwords'       => 'ucwords($0)',
        // 字符串剔除两端空白
        'trim'          => 'trim($0, ...)',
        // 字符串中字符位置
        'index'         => 'strpos($1, $0)',
        // 字符串中字符
        'char'          => '$0[$1]',
        // 字符串md5值
        'md5'           => 'md5($0)',
        // 字符串hash值
        'hash'          => 'hash($1, $0)',
        // 字符串HTML转义
        'escape'        => 'htmlentities($0)',
        // 字符串HTML反转义
        'unescape'      => 'html_entity_decode($0)',
        // 字符串URL转义
        'urlencode'     => 'urlencode($0)',
        // 字符串URL反转义
        'urldecode'     => 'urldecode($0)',
        // 数组转为JSON
        'jsonencode'    => 'jsonencode($0)',
        // JSON转为数组
        'jsondenode'    => 'jsondenode($0)',
        // 数组长度
        'count'         => 'count($0)',
        // 创建范围数组
        'range'         => 'range($0, $1)',
        // 字符串分割为数组
        'split'         => 'explode($1, $0)',
        // 数组连接成字符串
        'join'          => 'implode($1, $0)',
        // 获取数组keys
        'keys'          => 'array_keys($0)',
        // 获取数组values
        'values'        => 'array_values($0)',
        // 数字绝对值
        'abs'           => 'abs($0)',
        // 数字向上取整
        'ceil'          => 'ceil($0)',
        // 数字向下取整
        'floor'         => 'floor($0)',
        // 数字四舍五入
        'round'         => 'round($0, $1)',
        // 数字随机值
        'rand'          => 'rand($0, $1)',
        // 数字格式化
        'number_format' => 'number_format($0)',
        // 时间戳
        'time'          => 'time()',
        // 时间格式化
        'date'          => 'date($1, $0)',
    ];
    
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::get('template')) {
            if ($functions = Arr::pull($config, 'functions')) {
                self::$functions = $functions + self::$functions;
            }
            self::$config = $config + self::$config;
        }
    }
    
    public static function complie($str)
    {
        return self::textParse(self::tagParse(trim($str)));
    }
    
    public static function complieBlock($str)
    {
        self::readExtends($str = trim($str));
        return self::textParse(self::tagParse($str));
    }

    public static function complieExtends($self, $parent)
    {
        $str = self::mergeExtends(trim($self), $parent);
        return self::textParse(self::tagParse($str));
    }

    /*
     * 插值模版语法解析
     */
    protected static function textParse($str)
    {
        // 以文插值左边界符分割文本
        $i = 1;
        $arr = explode(self::$config['text_border_sign'][0], $str);
        if (($count = count($arr)) < 2) {
            return $str;
        }
        $res = $arr[0];
        // 循环处理被分割文本
        while ($i < $count) {
            // 不解析，原样输出
            if (substr($res, -1) === self::$config['verbatim_text_sign']) {
                $res .= self::$config['text_border_sign'][0].$arr[$i];
                $i++;
                continue;
            }
            // 是否转义
            $escape = self::$config['auto_escape_text'];
            if (in_array($c = $arr[$i][0], self::$config['text_escape_sign'])) {
                $arr[$i] = substr($arr[$i], 1);
                $escape  = !array_search($c, self::$config['text_escape_sign']);
            }
            // 读取解析插值语句
            $ret = ['continue' => false];
            do {
                if ($i >= $count) {
                    throw new TemplateException('textParse error: 不完整的插值语句');
                }
                $val = $arr[$i];
                $ret  = self::readValue(
                    $val,
                    self::$config['text_border_sign'][0],
                    self::$config['text_border_sign'][1],
                    $ret['continue'] ? $ret : null
                );
                $i++;
            } while ($ret['continue'] === true);
            $code = self::readUnit($ret['code'], $ret['vars']);
            if ($escape) {
                $res .= self::wrapCode("echo htmlentities($code);");
            } else {
                $res .= self::wrapCode("echo $code;");
            }
            if(strlen($val) - $ret['pos'] > 1) {
                $res .= substr($val, $ret['pos'] + 1);
            }
        }
        return $res;
    }
    
    /*
     * 标签模版语法解析
     */
    protected static function tagParse($str)
    {
        // extends语句
        if ($res = self::readExtends($str)) {
            return $res;
        }
        // include语句
        if (preg_match_all(
            '/<'.self::$config['include_tag'].' +name *= *"([\w|\/|-]+)" *\/>/',
            $str, $matchs, PREG_OFFSET_CAPTURE
        )) {
            $pos = 0;
            $tmp = '';
            foreach ($matchs[0] as $i => $match) {
                $tmp .= substr($str, $pos, $match[1] - $pos);
                $tmp .= self::wrapCode('include '.self::$config['view_include_method'].'("'.$matchs[1][$i][0].'");');
                $pos  = strlen($match[0]) + $match[1];
            }
            $str = $tmp.substr($str, $pos);
        }
        // 其它语句
        $assign_regex = preg_quote(self::$config['assign_attr_prefix']).'\w+';
        $struct_regex = preg_quote(self::$config['struct_attr_prefix']).'\w+';
        if (!preg_match_all("/<(\w+) +($assign_regex|$struct_regex).+/", $str, $matchs, PREG_OFFSET_CAPTURE)) {
            return $str;
        }
        $res = '';
        $pos = 0;
        $end = [];
        foreach ($matchs[0] as $i => $match) {
            if ($pos > $match[1]) {
                throw new TemplateException("tagParse error: 文本读取偏移地址错误");
            }
            // 读取左侧HTML
            $left  = substr($str, $pos, $match[1] - $pos);
            // 读取空白内容
            $blank = self::readLeftBlank($left);
            // 拼接左侧内容，如有为闭合语句，尝试闭合处理。
            $res  .= $end ? self::completeEndTag($left, $end) : $left;
            // 读取解析标签内代码
            $ret   = self::readValue($match[0], '<', '>');
            $tag   = self::readTag($ret['code'].'>', $ret['vars']);
            if ($tag['is_else']) {
                // 处理if与elseif else的衔接
                $l = 2 + strlen(PHP_EOL);
                if (substr($res, -$l, 2) == '?>') {
                    $res = substr($res, 0, -$l).substr(implode(PHP_EOL.$blank, $tag['code']), 6);
                } else {
                    throw new TemplateException('tagParse error: 衔接if else失败');
                }
            } else {
                $res .= implode(PHP_EOL.$blank, $tag['code']);
            }
            // 补全空白内容
            $res .= PHP_EOL.$blank;
            // 如果是空白标签则忽略标签HTML代码
            if ($matchs[1][$i][0] != self::$config['blank_tag']) {
                $res .= $tag['html'];
            }
            // 如果是自闭合标签则自行添加PHP闭合
            // 否则增加未闭合标签语句数据供下步处理。
            if (substr($tag['html'], -2, 1) === '/') {
                $res .= str_repeat(PHP_EOL.$blank.self::wrapCode('}'), $tag['count']);
            } else {
                $end[] = [
                    // HTML闭合标签层数
                    'num'   => 0,
                    // 补全的PHP闭合标签数
                    'count' => $tag['count'],
                    // HTML标签名
                    'tag'   => $matchs[1][$i][0]
                ];
            }
            // 处理开始标签行右侧内容，尝试闭合处理。
            if (strlen($match[0]) - $ret['pos'] > 2) {
                $right = substr($match[0], $ret['pos'] + 1);
                $res  .= $end ? self::completeEndTag($right, $end, $blank) : $right;
            }
            // 重设文本处理位置
            $pos = strlen($match[0]) + $match[1];
        }
        // 处理最后部分
        $tmp = substr($str, $pos);
        $res.= $end ? self::completeEndTag($tmp, $end) : $tmp;
        return $res;
    }
    
    /*
     * 读取解析模版标签
     */
    protected static function readTag($tag, $vars)
    {
        // 标签HTML代码
        $html = '';
        // 标签PHP代码
        $code = [];
        // 标签内结构语句条数
        $count   = 0;
        $is_else = false;
        $assign_regex = preg_quote(self::$config['assign_attr_prefix']);
        $struct_regex = preg_quote(self::$config['struct_attr_prefix']);
        $regex = "/ +($assign_regex|$struct_regex)([a-zA-Z_]\w*)( *= *\\$([1-9]))?/";
        if (preg_match_all($regex, $tag, $matchs, PREG_OFFSET_CAPTURE)) {
            $pos = 0;
            foreach ($matchs[1] as $i => $match) {
                $tmp = trim(substr($tag, $pos, $matchs[0][$i][1] - $pos));
                if ($tmp) {
                    $html .= $tmp;
                }
                $attr = $matchs[2][$i][0];
                // 是否为赋值语句
                if ($matchs[1][$i][0] == self::$config['assign_attr_prefix']) {
                    $value  = self::readValue(substr(trim($vars[$matchs[4][$i][0] - 1]), 1, -1));
                    $code[] = self::wrapCode('$'.$attr.' = '.self::readExp($value['code'], $value['vars']).';');
                } else {
                    $count++;
                    if ($attr == 'else' || $attr == 'elseif') {
                        $is_else = true;
                        if ($code) {
                            throw new TemplateException("readTag error: 单个标签内else或elseif前不允许有其它语句");
                        }
                    }
                    if ($attr != 'else') {
                        if (empty($matchs[3][$i][0])) {
                            throw new TemplateException("readTag error: 标签属性值不能为空");
                        }
                        $val = substr(trim($vars[$matchs[4][$i][0] - 1]), 1, -1);
                    }
                    $code[] = self::wrapCode(self::readStruct($attr, $val ?? null));
                }
                $pos = $matchs[0][$i][1] + strlen($matchs[0][$i][0]);
            }
            $html .= substr($tag, $pos);
        }
        if ($vars) {
            $html = self::restoreStrvars($html, $vars);
        }
        return compact('html', 'code', 'count', 'is_else');
    }
    
    /*
     * 合并完成模版标签闭合
     */
    protected static function completeEndTag($str, &$end, $blank = null)
    {
        $res = '';
        do {
            $i = count($end) - 1;
            $tag = $end[$i]['tag'];
            if (!preg_match_all('/(<'.$tag.'|<\/'.$tag.'>)/', $str, $matchs, PREG_OFFSET_CAPTURE)) {
                // 无匹配则直接拼接剩余部分返回
                return $res.$str;
            }
            $pos = 0;
            foreach ($matchs[0] as $match) {
                $tmp  = substr($str, $pos, $match[1] - $pos);
                $res .= $tmp;
                // 重设读取位置
                $pos  = strlen($match[0]) + $match[1];
                // 新开始标签则计数加一
                if ($match[0][1] !== '/') {
                    $res .= $match[0];
                    $end[$i]['num']++;
                } else {
                    // 非最后结束标签则计数减一
                    if ($end[$i]['num'] > 0) {
                        $res .= $match[0];
                        $end[$i]['num']--;
                    // 最后结束标签则处理标签闭合
                    } else {
                        if ($tag !== self::$config['blank_tag']) {
                            $res .= $match[0];
                        }
                        $left = $blank ?? self::readLeftBlank($tmp);
                        $res .= str_repeat(PHP_EOL.$left.self::wrapCode('}'), $end[$i]['count']);
                        // 处理完成，踢出当前任务，继续下一个任务。
                        array_pop($end);
                        break;
                    }
                }
            }
            // 重设字符串
            $str  = substr($str, $pos);
        } while ($i > 0);
        if ($str) {
            // 拼接剩余部分
            $res .= $str;
        }
        return $res;
    }
    
    /*
     * 读取extends
     */
    protected static function readExtends(&$str, $check = false)
    {
        if (preg_match('/^<'.self::$config['extends_tag'].' +name *= *"([\w|\/|-]+)" *\/>/', $str, $match)) {
            $str = substr($str, strlen($match[0]));
            return sprintf(
                self::wrapCode('if (%s("%s", __FILE__, %s)) return include __FILE__;'),
                self::$config['view_extends_method'],
                $match[1],
                $check ? 'true' : 'false'
            );
        }
    }
    
    /*
     * 合并布局模版
     */
    protected static function mergeExtends($self, $parent)
    {
        $res = self::readExtends($self, true).PHP_EOL;
        $block_regex = '/<'.self::$config['block_tag'].' +name *= *"(\w+)" *>/';
        $block_end   = '</'.self::$config['block_tag'].'>';
        // 匹配读取子模版
        if (preg_match_all($block_regex, $self, $self_matchs, PREG_OFFSET_CAPTURE)) {
            $pos = 0;
            $sub_blocks = [];
            foreach ($self_matchs[0] as $i => $match) {
                if ($match[1] >= $pos) {
                    $pos = stripos($self, $block_end, $match[1]);
                    if ($pos) {
                        $name = $self_matchs[1][$i][0];
                        $start = $match[1] + strlen($match[0]);
                        $sub_blocks[$name] = substr($self, $start, $pos - $start);
                        continue;
                    }
                }
                throw new TemplateException('mergeExtends error: 子模版代码块未闭合');
            }
        }
        // 匹配读取父模版
        if (preg_match_all($block_regex, $parent, $parent_matchs, PREG_OFFSET_CAPTURE)) {
            $s_pos = 0;
            $e_pos = 0;
            $regex  = '/'.implode(' *'.self::$config['parent_block_sign'].' *', self::$config['text_border_sign']).'/';
            foreach ($parent_matchs[0] as $i => $match) {
                if ($match[1] >= $e_pos) {
                    $res .= substr($parent, $s_pos, $match[1] - $s_pos);
                    $e_pos = stripos($parent, $block_end, $match[1]);
                    if ($e_pos) {
                        $block_name = $parent_matchs[1][$i][0];
                        // 继承或重写父模版块代码
                        if (isset($sub_blocks[$block_name])) {
                            $content = $sub_blocks[$block_name];
                            // 匹配父模版块占位符，并替换成父模版块代码
                            $arr = preg_split($regex, $content, 2);
                            if (count($arr) == 2) {
                                $start = $match[1] + strlen($match[0]);
                                $res  .= implode(substr($parent, $start, $e_pos - $start), $arr);
                            } else {
                                $res  .= $content;
                            }
                        } else {
                            $start = $match[1] + strlen($match[0]);
                            $res  .= substr($parent, $start, $e_pos - $start);
                        }
                        $s_pos = $e_pos + strlen($block_end);
                        continue;
                    }
                }
                throw new TemplateException('mergeExtends error: 父模版代码块未闭合');
            }
            if ($s_pos < strlen($parent)) {
                $res .= substr($parent, $s_pos);
            }
        } else {
            $res .= $parent;
        }
        return $res;
    }
    
    /*
     * 读取解析属性值
     */
    protected static function readValue($str, $start = null, $end = null, $continue = null)
    {
        $pos = $num = 0;
        // $continue不为空则继续上次内容处理，否则开始新处理
        if ($continue) {
            extract($continue);
            if ($start) {
                $var .= $start;
            }
        } else {
            $var    = '';
            $code   = '';
            $quote  = null;
            $vars   = [];
        }
        if($end) {
            $end_len = strlen($end);
        }
        $len = strlen($str);
        for($i = 0; $i < $len; $i++) {
            $char = $str[$i];
            // 处理引号
            if (self::isQuoteChar($char)) {
                if ($quote) {
                    if ($quote === $char) {
                        if ($continue) {
                            if (($i === 0 || $str[$i - 1] !== '\\')) {
                                $vars[] = $quote.$var.$quote;
                                $var   = '';
                                $num   = 0;
                                $quote = null;
                                $code .= '$'.count($vars);
                            } else {
                                $var  .= $char;
                            }
                        } else {
                            if ($i - $num === 1 || ($str[$i - 1] !== '\\')) {
                                $vars[] = $quote.$var.$quote;
                                $var   = '';
                                $num   = 0;
                                $quote = null;
                                $code .= '$'.count($vars);
                            } else {
                                $var  .= $char;
                            }
                        }
                    } else {
                        $var .= $char;
                    }
                } else {
                    $num = $i;
                    $quote = $char;
                }
            } elseif ($end && ($i - $end_len) > -2) {
                if ($quote){
                    $var  .= $char;
                } elseif (substr($str, $i + 1 - $end_len, $end_len) !== $end) {
                    $code .= $char;
                } else {
                    if ($end_len > 1) {
                        $code = substr($code, 0, 1 - $end_len);
                    }
                    return ['continue' => false, 'code' => $code, 'pos' => $i, 'vars' => $vars];
                }
            } elseif (self::isNewlineChar($char)) {
                throw new TemplateException("readValue error: 不允许有换行符");
            } else {
                if ($quote) {
                    $var  .= $char;
                } else {
                    $code .= $char;
                }
            }
        }
        if (!$quote) {
            return ['continue' => false, 'code' => $code, 'pos' => $pos, 'vars' => $vars];
        }
        return ['continue' => true, 'code' => $code, 'pos' => $len, 'vars' => $vars, 'var' => $var, 'quote' => $quote];
    }
    
    /*
     * 读取语句结构
     */
    protected static function readStruct($struct, $val = null)
    {
        if ($val) {
            $attr = self::readValue($val);
        }
        switch ($struct) {
            case 'if':
                return 'if ('.self::readExp($attr['code'], $attr['vars']).') {';
            case 'elseif':
                return 'elseif ('.self::readExp($attr['code'], $attr['vars']).') {';
            case 'else':
                return 'else {';
            case 'each':
                $arr = explode(' in ', $attr['code'], 2);
                if (isset($arr[1])) {
                    $kv = explode(':', $arr[0]);
                    $ct = count($kv);
                    $each = $arr[1];
                    if ($ct == 1) {
                        $as = '$'.$kv[0];
                    } elseif ($ct == 2) {
                        if (empty($kv[0])) {
                            if (self::isVarnameChars($kv[1])) {
                                $as = '$'.$kv[1];
                            }
                        } elseif (empty($kv[1])) {
                            if (self::isVarnameChars($kv[0])) {
                                $as = '$'.$kv[0].' => $__';
                            }
                        } else {
                            if (self::isVarnameChars($kv[0]) && self::isVarnameChars($kv[1])) {
                                $as = '$'.$kv[0].' => $'.$kv[1];
                            }
                        }
                    }
                // 无in关键字则默认使用$key和$val表示键值
                } else {
                    $each = $arr[0];
                    $as = '$key => $val';
                }
                return 'foreach('.self::readArg($each, $attr['vars']).' as '.$as.') {';
            case 'for':
                if (count($arr = explode(';', $attr['code'], 2)) == 2) {
                    foreach($arr as $v) {
                        $ret[] = self::readExp($v, $attr['vars']);
                    }
                    return 'for ('.implode(';', $ret).') {';
                }
                throw new TemplateException("readStruct error: for语句格式错误");
        }
        throw new TemplateException("readStruct error: 非法语句 $struct");
    }

    /*
     * 读取语句单元
     */
    protected static function readUnit($str, $vars)
    {
        $i    = 0;
        $str  = trim($str);
        $len  = strlen($str);
        $code = '';
        $prev = null;
        while ($i < $len) {
            $ret = self::readWord(substr($str, $i));
            $i += $ret['seek'];
            switch ($ret['end']) {
                // 数组或函数
                case '.':
                    if (($prev === '.' || empty($code)) && empty($ret['code'])) {
                        throw new TemplateException('read_unit error: '.$str);
                    }
                    if (empty($code)) {
                        $code = self::replaceVar($ret['code']);
                    } elseif (!empty($ret['code'])){
                        $code .= '[\''.$ret['code'].'\']'; 
                    }
                    break;
                // 原生PHP函数或静态方法
                case '@':
                    if (!self::$config['enable_native_func']) {
                        throw new TemplateException("readUnit error: 未开启原生PHP函数支持");
                    }
                    if ($code || $ret['code'] || !($pos = strpos($str, '('))) {
                        throw new TemplateException("readUnit error: 非法字符");
                    }
                    foreach ($arr = explode('.', substr($str, $i, $pos - 1)) as $item) {
                        if (!self::IsVarnameChars($item)) {
                            throw new TemplateException("readUnit error: 非法字符 $item");
                        }
                    }
                    $i = $pos + 1;
                    $args = implode(', ', self::readFuncArgs($str, $i, $vars));
                    if (($c = count($arr)) == 1) {
                        $code = "$arr[0]($args)";
                    } else {
                        $code = implode('\\', array_slice($arr, 0, -1)).'::'.$arr[$c - 1]."($args)";
                    }
                    break;
                // 对象
                case '->':
                    if (($code || $ret['code']) && self::beforeIsVarnameChars($str, ['('])) {
                        if ($prev === '@') {
                            $code .= self::replaceMethod($ret['code']).'()->';
                        } else {
                            $code .= $ret['code'].'->';
                            $prev = '->';
                        }
                        break;
                    }
                    throw new TemplateException("readUnit error: $str");
                // 括号或函数调用
                case '(':
                    // 作为括号使用
                    if (empty($ret['code'])) {
                        if (!$code) {
                            $str = rtrim($str);
                            if (substr($str, -1) === ')') {
                                return '('.self::readArg(substr($str, $i, -1), $vars).')';
                            }
                        }
                        throw new TemplateException("readUnit error: 括号未闭合或括号前存在代码");
                    }
                    $args = self::readFuncArgs($str, $i, $vars, $code ? [$code] : []);
                    $code = self::replaceFunc($ret['code'], $args);
                    break;
                // 数组
                case '[':
                    if (empty($ret['code'])) {
                        if ($prev === '.' || empty($code)) {
                            throw new TemplateException("readUnit error: $str");
                        }
                    }
                    $pos = self::findEndPos(substr($str, $i), '[', ']');
                    $arg = self::readArg(substr($str, $i, $pos), $vars, $type);
                    $i += $pos + 1;
                    if ($code) {
                        if ($ret['code']) {
                            $code .= '[\''.$ret['code'].'\']';
                        }
                        $code .= '['.$arg.']';
                    } else {
                        $code = self::replaceVar($ret['code']).'['.$arg.']';
                    }
                    break;
                // 三元表达式
                case '?':
                    if (empty($code)) {
                        $code = self::replaceVar($ret['code']);
                    } elseif($ret['code']) {
                        $code .= '[\''.$ret['code'].'\']';
                    }
                    if ($str[$i] == ':' || $str[$i] == '?') {
                        return $code.' ?'.$str[$i].' '.self::readArg(substr($str, $i + 1), $vars);
                    } else {
                        $pos = 0;
                        while ($pos = strpos($str, ':', $pos)) {
                            if (substr($str, $pos + 1, 1) != ':') {
                                $left  = substr($str, 0, $pos);
                                $right = substr($str, $pos + 1);
                                break;
                            }
                        }
                        if (isset($left)) {
                            return $code.' ? '.self::readArg($left, $vars). ' : ' .self::readArg($right, $vars);
                        }
                    }
                    throw new TemplateException("readUnit error: $str");
                case '':
                    if (empty($code)) {
                        return self::replaceVar($ret['code']);
                    } elseif(!empty($ret['code'])) {
                        return $code.'[\''.$ret['code'].'\']';
                    }
                    return $code;
                default:
                    if (in_array($ret['end'], ['+', '-', '*', '/', '%'])) {
                        if (empty($code)) {
                            $code = self::replaceVar($ret['code']);
                        } elseif (!empty($ret['code'])){
                            $code .= '[\''.$ret['code'].'\']'; 
                        }
                        return $code.' '.$ret['end'].' '.self::readArg(substr($str, $i), $vars);
                    }
            }
            $prev = $ret['end'];
        }
        return $code;
    }
    
    /*
     * 读取表达式
     */
    protected static function readExp($str, $vars)
    {
        $exp = self::$operators ?? self::$operators = array_map(function ($v) {
            return preg_quote($v);
        } , ['!', '&', '|', '=', '>', '<', '+', '-', '*', '/', '%']);
        if (!preg_match_all('/('.implode('|', $exp).')/', $str, $matchs, PREG_OFFSET_CAPTURE)) {
            return self::readArg($str, $vars);
        }
        $pos = 0;
        $res = '';
        foreach ($matchs[0] as $match) {
            if ($match[1] > $pos) {
                $res.= self::readExpVal(substr($str, $pos, $match[1] - $pos), $vars);
            }
            $res.= $match[0];
            $pos = strlen($match[0]) + $match[1];
        }
        if ($pos < strlen($str)) {
            $res.= self::readExpVal(substr($str, $pos), $vars);
        }
        return $res;
    }
    
    protected static function readExpVal($str, $vars)
    {
        $ret = self::readBlank($str);
        return $ret['left'].self::readArg($ret['str'], $vars).$ret['right'];
    }
    
    /*
     * 读取关键字
     */
    protected static function readWord($str)
    {
        $code = '';
        $len  = strlen($str);
        $is_blank_end  = false;
        for($i = 0; $i < $len; $i++) {
            $c = $str[$i];
            if (self::isVarnameChar($c)) {
                if ($is_blank_end/* || (!$code && is_numeric($c))*/) {
                    throw new TemplateException("readWord error: var字符中不能有空白字符");
                }
                $code .= $c;
            } else {
                if(in_array($c, ['.', '[', '(', '?', '@'], true)) {
                    return ['code' => $code, 'seek' => $i + 1, 'end' => $c];
                } elseif (in_array($c, ['+', '-', '*', '/', '%'], true)) {
                    if ($c === '-' && $str[$i + 1] == '>') {
                        return ['code' => $code, 'seek' => $i + 2, 'end' => '->'];
                    }
                    return ['code' => $code, 'seek' => $i + 1, 'end' => $c];
                } elseif (self::isBlankChar($c)) {
                    if ($code) {
                        $is_blank_end = true;
                    }
                } else {
                    throw new TemplateException("readWord error: 非法字符 $c");
                }
            }
        }
        return ['code' => $code, 'seek' => $len, 'end' => ''];
    }
    
    /*
     * 读取参数
     */
    protected static function readArg($str, $vars, &$type = null)
    {
        $str = trim($str);
        // bool或null
        if ($str === 'true' || $str === 'false' || $str === 'null') {
            $type = $str;
            return $str;
        // 被替换的字符串变量
        } elseif (preg_match("/^\\$([1-9][0-9]*)$/", $str, $match)) {
            $type = 'string';
            return $vars[$match[1] - 1];
        // 数字
        } elseif (is_numeric($str)) {
            $type = 'number';
            return $str;
        // 数组
        } elseif ($str[0] === '[' || $str[0] === '{') {
            $type = 'array';
            return self::readArray($str, $vars);
        // 其它
        } else {
            $type = 'mixed';
            return self::readUnit($str, $vars);
        }
    }
    
    /*
     * 读取数组
     */
    protected function readArray($str, $vars)
    {
        return "jsondecode('".self::restoreStrvars($str, $vars)."')";
    }
    
    /*
     * 读取两侧空白符
     */
    protected static function readBlank($str)
    {
        $lpos = $rpos  = 0;
        $left = $right = $tmp = '';
        $len  = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            if (self::isBlankChar($str[$i])) {
                $left .= $str[$i];
            } else {
                $lpos = $i;
                break;
            }
        }
        if (strlen($left) === $len) {
            return ['left' => $left, 'right' => '', 'str' => ''];
        } else {
            for ($i = $len - 1; $i >= 0; $i--) {
                if (self::isBlankChar($str{$i})) {
                    $right .= $str{$i};
                } else {
                    $rpos = $i;
                    break;
                }
            }
            return ['left' => $left, 'right' => $right, 'str'=> substr($str, $lpos, $rpos - $lpos + 1)];
        }
    }
    
    /*
     * 读取左边空白符
     */
    protected static function readLeftBlank($str)
    {
        $blank = '';
        $i = strlen($str) - 1;
        while ($i >= 0) {
            if (isset($str[$i])) {
               if (self::isBlankChar($str[$i])) {
                   $blank .= $str[$i];
               } else {
                   return $blank;
               }
            }
            $i--;
        }
    }
    
    /*
     * 读取函数值
     */
    protected static function readFuncArgs($str, &$i, $vars, $args = [])
    {
        $pos = self::findEndPos(substr($str, $i), '(', ')');
        $tmp = trim(substr($str, $i, $pos));
        if (!empty($tmp)) {
            foreach (explode(',', $tmp) as $v) {
                $args[] = self::readArg($v, $vars);
            }
        }
        $i += $pos + 1;
        return $args;
    }
    
    /*
     * 替换变量
     */
    protected static function replaceVar($var)
    {
        return self::$var_alias[$var] ?? '$'.$var;
    }
    
    /*
     * 替换函数
     */
    protected static function replaceFunc($name, $args)
    {
        if (isset(self::$functions[$name])) {
            if ($args) {
                foreach ($args as $i => $arg) {
                    $arr['$'.$i] = $arg;
                }
                return strtr(self::$functions[$name], $arr);
            } else {
                return self::$functions[$name];
            }
        }
        $args = $args ? ', '.implode(', ', $args) : '';
        return self::$config['view_filter_method']."('$name'$args)";
    }

    /*
     * 还原变量
     */ 
    protected static function restoreStrvars($code, $vars)
    {
        foreach ($vars as $i => $v) {
            $arr['$'.($i + 1)] = $v;
        }
        return strtr($code, $arr);
    }
    
    /*
     * 寻找语句结束符位置
     */ 
    protected static function findEndPos($str, $start, $end)
    {
        $num = 0;
        $len = strlen($str);
        for($i = 0; $i < $len; $i++) {
            $c = $str[$i];
            if ($c === $start) {
                $num++;
            } elseif ($c === $end) {
                if ($num === 0) {
                    return $i;
                }
                $num--;
            }
        }
        throw new TemplateException("findEndPos errer: 没有找到结束符");
    }
    
    /*
     * 包装PHP代码
     */  
    protected static function wrapCode($code)
    {
        return "<?php $code ?>";
    }
    
    /*
     * 是否引号字符
     */  
    protected static function isQuoteChar($char)
    {
        return $char === '"' || $char === "'";
    }
    
    /*
     * 是否空白字符
     */ 
    protected static function isBlankChar($char)
    {
        return $char === ' ' || $char === "\t";
    }
    
    /*
     * 是否换行字符
     */ 
    protected static function isNewlineChar($char)
    {
        return $char === "\r" || $char === "\n";
    }
    
    /*
     * 是否变量名字符
     */ 
    protected static function isVarnameChar($char)
    {
        return (
            ($ascii = ord($char)) === 95 || ($ascii > 47 && $ascii < 58) ||
            ($ascii > 64 && $ascii < 91) || ($ascii > 96 && $ascii < 123)
        );
    }
    
    /*
     * 是否变量名字符串
     */ 
    protected static function isVarnameChars($str)
    {
        $len = strlen($str);
        if($len > 0 && self::isVarnameChar($str[0]) && !is_numeric($str[0])) {
            for ($i = 1; $i < $len; $i++) {
                if (!self::isVarnameChar($str[$i])) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
    
    /*
     * 查找特定字符前的字符串是否为合法变量名
     */ 
    protected static function beforeIsVarnameChars($str, $finds, $miss_return = false)
    {
        foreach ($finds as $find) {
            if (($p = strpos($str, $end)) !== false) {
                $pos[] = $p;
            }
        }
        return isset($pos) ? self::isVarnameChars(substr($str, 0, min($pos))) : $miss_return;
    }
}
Template::init();
