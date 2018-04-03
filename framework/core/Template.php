<?php
namespace framework\core;

use framework\util\Arr;

class Template
{
    protected static $init;
    // 
    protected static $operators = [
        '!', '&', '|', '=', '>', '<', '+', '-', '*', '/', '%', '?', ':'
    ];
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
        // 默认是否转义文本
        'auto_escape_text'      => true,
        // 文本转义符号与反转义符号
        'text_escape_sign'      => ['#', '!'],
        // 文本插入左右边界符号
        'text_border_sign'      => ['{{', '}}'],
        // 父模版块标签
        'parent_block_sign'     => '@parent',
        // include方法名
        'view_include_method'   => View::class.'::path',
        // extends方法名
        'view_extends_method'   => View::class.'::extends',
    ];
    // 内置函数
    protected static $functions = [
        // 类型判断
        'is'        => '("is_$1")($0)',
        // 是否为空
        'has'       => 'isset($0)',
        // 默认值
        'default'   => '$0 ?? $1',
        // 转为字符串
        'str'       => 'strval($0)',
        // 转为数字
        'num'       => '($0+0)',
        // 字符串拼接
        'concat'    => '$0.$1',
        // 字符串拼接
        'sprintf'   => 'sprintf($0, $1, ...)',
        // 字符串替换
        'replace'   => 'str_replace($1, $2, $0)',
        // 字符串截取
        'substr'    => 'substr($0, $1, $2)',
        // 字符串长度
        'length'    => 'strlen($0)',
        // 字符串大写
        'lower'     => 'strtolower($0)',
        // 字符串小写
        'upper'     => 'strtoupper($0)',
        // 字符串首字母大写
        'ucfirst'   => 'ucfirst($0)',
        // 字符串剔除两端空白
        'trim'      => 'trim($0, ...)',
        // 字符串中字符位置
        'index'     => 'strpos($1, $0)',
        // 字符串中字符
        'char'      => '$0[$1]',
        // 字符串HTML转义
        'escape'    => 'htmlspecialchars($0)',
        // 字符串HTML反转义
        'unescape'  => 'htmlspecialchars_decode($0)',
        // 字符串URL转义
        'urlencode' => 'urlencode($0)',
        // 字符串URL反转义
        'urldecode' => 'urldecode($0)',
        // 数组转为JSON
        'jsonencode'=> 'jsonencode($0)',
        // JSON转为数组
        'jsondenode'=> 'jsondenode($0)',
        // 数组长度
        'count'     => 'count($0)',
        // 字符串分割为数组
        'split'     => 'explode($1, $0)',
        // 数组连接成字符串
        'join'      => 'implode($1, $0)',
        // 获取数组keys
        'keys'      => 'array_keys($0)',
        // 获取数组values
        'values'    => 'array_values($0)',
        // 数字绝对值
        'abs'       => 'abs($0)',
        // 数字向上取整
        'ceil'      => 'ceil($0)',
        // 数字向下取整
        'floor'     => 'floor($0)',
        // 数字四舍五入
        'round'     => 'round($0)',
        // 数字随机值
        'rand'      => 'rand($0, $1)',
        // 数字格式化
        'number'    => 'number_format($0)',
        // 时间戳
        'time'      => 'time()',
        // 时间格式化
        'date'      => 'date($1, $0)',
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
    /*
    public static function complieInline($self, $contents)
    {
        $str = self::mergeInline(trim($str), $contents);
        return self::textParse(self::tagParse($str));
    }
    */
    public static function complieExtends($self, $parent)
    {
        $str = self::mergeExtends(trim($self), $parent);
        return self::textParse(self::tagParse($str));
    }

    /*
     * 模版语法解析
     */
    protected static function textParse($str)
    {
        $i = 1;
        $pairs = explode(self::$config['text_border_sign'][0], $str);
        if (($count = count($pairs)) < 2) {
            return $str;
        }
        $res = $pairs[0];
        while ($i < $count) {
            $escape = self::$config['auto_escape_text'];
            if (in_array($c = $pairs[$i][0], self::$config['text_escape_sign'])) {
                $pairs[$i] = substr($pairs[$i], 1);
                $escape = !array_search($c, self::$config['text_escape_sign']);
            }
            $ret = ['continue' => false];
            do {
                if ($i < $count) {
                    $pair = $pairs[$i];
                    $ret  = self::readAttrValue(
                        $pair,
                        self::$config['text_border_sign'][0],
                        self::$config['text_border_sign'][1],
                        $ret['continue'] ? $ret : null
                    );
                    $i++;
                } else {
                    throw new \Exception('textParse error: '.$count);
                }
            } while ($ret['continue'] === true);
            if ($escape) {
                $res .= '<?php echo htmlentities('.self::readUnit($ret['code'], $ret['vars']).'); ?>';
            } else {
                $res .= '<?php echo '.self::readUnit($ret['code'], $ret['vars']).'; ?>';
            }
            if(strlen($pair) - $ret['pos'] > 1) {
                $res .= substr($pair, $ret['pos'] + 1);
            }
        }
        return $res;
    }
    
    /*
     * 模版标签语法解析
     */
    public static function tagParse($str)
    {
        if ($res = self::readExtends($str)) {
            return $res;
        }
        $include_regex = '/<'.self::$config['include_tag'].' +name *= *"([\w|\/|-]+)" *\/>/';
        if (preg_match_all($include_regex, $str, $include_matchs, PREG_OFFSET_CAPTURE)) {
            $pos = 0;
            $tmp = '';
            foreach ($include_matchs[0] as $i => $match) {
                $tmp .= substr($str, $pos, $match[1] - $pos);
                $tmp .= '<?php include '.self::$config['view_include_method'].'("'.$include_matchs[1][$i][0].'"); ?>';
                $pos = strlen($match[0]) + $match[1];
            }
            $str = $tmp.substr($str, $pos);
        }
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
                throw new \Exception("Tag parse error");
            }
            $left  = substr($str, $pos, $match[1] - $pos);
            $blank = self::readLeftBlank($left);
            $res  .= $end ? self::completeEndTag($left, $end) : $left;
            $ret   = self::readAttrValue($match[0], '<', '>');
            $tag   = self::readTag($ret['code'].'>', $ret['vars']);
            $res  .= implode(PHP_EOL.$blank, $tag['code']);
            if ($matchs[1][$i][0] !== self::$config['blank_tag']) {
                $res .= PHP_EOL.$blank.$tag['html'];
            }
            if ($tag['end']) {
                if (substr($tag['html'], -2, 1) === '/') {
                    $res .= PHP_EOL.$blank.'<?php '.str_pad('', $tag['count'], '}').' ?>';
                } else {
                    $end[] = [
                        'num'   => 0,
                        'count' => $tag['count'],
                        'tag'   => $matchs[1][$i][0]
                    ];
                }
                if (strlen($match[0]) - $ret['pos'] > 2) {
                    $end_html = substr($match[0], $ret['pos'] + 1);
                    if ($end) {
                        $res .= self::completeEndTag($end_html, $end, $blank);
                    } else {
                        $res .= $end_html;
                    }
                    
                }
                $pos = strlen($match[0]) + $match[1];
            }
        }
        $tmp = substr($str, $pos);
        $res .= $end ? self::completeEndTag($tmp, $end) : $tmp;
        return $res;
    }
    
    /*
     * readExtends
     */
    protected static function readExtends(&$str, $check = false)
    {
        if (preg_match('/^<'.self::$config['extends_tag'].' +name *= *"([\w|\/|-]+)" *\/>/', $str, $match)) {
            $str = substr($str, strlen($match[0]));
            return sprintf(
                '<?php if (%s("%s", __FILE__, %s)) return include __FILE__; ?>',
                self::$config['view_extends_method'],
                $match[1],
                $check ? 'true' : 'false'
            );
        }
    }
    
    /*
     * 合并布局模版
     */
    public static function mergeExtends($self, $parent)
    {
        $res = self::readExtends($self, true).PHP_EOL;
        $block_regex = '/<'.self::$config['block_tag'].' +name *= *"(\w+)" *>/';
        $block_end   = '</'.self::$config['block_tag'].'>';
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
                throw new \Exception('extend_merge error');
            }
        }
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
                        if (isset($sub_blocks[$block_name])) {
                            $content = $sub_blocks[$block_name];
                            $pairs = preg_split($regex, $content, 2);
                            if (count($pairs) == 2) {
                                $start = $match[1] + strlen($match[0]);
                                $res  .= implode(substr($parent, $start, $e_pos - $start), $pairs);
                            } else {
                                $res  .= $content;
                            }
                        } else {
                            $start = $match[1] + strlen($match[0]);
                            $res .= substr($parent, $start, $e_pos - $start);
                        }
                        $s_pos = $e_pos + strlen($block_end);
                        continue;
                    }
                }
                throw new \Exception('extend_merge error');
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
     * 合并布局模版
     */
    /*
    public static function mergeInline($self, $contents)
    {
        if (preg_match_all('/^<inline +name *= *"([\w|\/|-]+)" *\/>/', $self, $matchs, PREG_OFFSET_CAPTURE)) {
            $pos = 0;
            $res = '';
            foreach ($matchs[0] as $i => $match) {
                $res .= substr($str, $pos, $match[1] - $pos);
                $res .= $contents[$matchs[1][$i][0]];
                $pos = strlen($match[0]) + $match[1];
            }
            return $res.substr($self, $pos);
        }
    }
    
    protected static function readInline($str, $check = false)
    {
        if (preg_match_all('/^<inline +name *= *"([\w|\/|-]+)" *\/>/', $str, $matchs)) {
            return sprintf(
                '<?php if ($_f = %s(["%s"], __FILE__, %s)) return include $_f; ?>',
                self::$config['view_inline_method'],
                implode('","', $matchs[1]),
                $check ? 'true' : 'false'
            );
        }
    }
    */
    
    protected static function readTag($tag, $vars)
    {
        $end  = false;
        $html = '';
        $code = [];
        $count = 0;
        $has_noas_attr = false;
        $assign_regex = preg_quote(self::$config['assign_attr_prefix']);
        $struct_regex = preg_quote(self::$config['struct_attr_prefix']);
        $regex = "/ +($assign_regex|$struct_regex)([a-zA-Z_]\w*)( *= *\\$([1-9]))?/";
        if (preg_match_all($regex, $tag, $matchs, PREG_OFFSET_CAPTURE)) {
            $start_pos = 0;
            foreach ($matchs[1] as $i => $match) {
                $tmp = trim(substr($tag, $start_pos, $matchs[0][$i][1] - $start_pos));
                if (!empty($tmp)) {
                    $html .= $tmp;
                }
                $attr = $matchs[2][$i][0];
                if ($matchs[1][$i][0] == self::$config['assign_attr_prefix']) {
                    $value = self::readAttrValue(substr(trim($vars[$matchs[4][$i][0] - 1]), 1, -1));
                    $code[] = '<?php $'.$attr.' = '.self::readExp($value['code'], $value['vars'], self::$operators).'; ?>';
                } else {
                    $count++;
                    if ($attr == 'else') {
                        $val = null;
                    } else {
                        if (empty($matchs[3][$i][0])) {
                            throw new \Exception("readTag error: $tag");
                        }
                        $val = substr(trim($vars[$matchs[4][$i][0] - 1]), 1, -1);
                    }
                    $attr_ret = self::readStructure($attr, $val);
                    $code[] = '<?php '.$attr_ret['code'].' ?>';
                    if(!$end) {
                        $end = $attr_ret['end'];
                    }
                }
                $start_pos = $matchs[0][$i][1] + strlen($matchs[0][$i][0]);
            }
            $html .= substr($tag, $start_pos);
        }
        if ($vars) {
            $html = self::restoreStrvars($html, $vars);
        }
        return compact('html', 'code', 'end', 'count');
    }
    
    public static function readAttrValue($str, $start = null, $end = null, $continue = null)
    {
        $pos = 0;
        $num = 0;
        if ($continue) {
            extract($continue, EXTR_SKIP);
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
            if (self::isQuoteChar($char)) {
                if ($quote) {
                    if ($quote === $char) {
                        if ($continue) {
                            if (($i === 0 || $str{$i-1} !== '\\')) {
                                $vars[] = $quote.$var.$quote;
                                $var = '';
                                $num = 0;
                                $quote = null;
                                $code .= '$'.count($vars);
                            } else {
                                $var .= $char;
                            }
                        } else {
                            if (($i - $num === 1) || ($str{$i-1} !== '\\')) {
                                $vars[] = $quote.$var.$quote;
                                $var = '';
                                $num = 0;
                                $quote = null;
                                $code .= '$'.count($vars);
                            } else {
                                $var .= $char;
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
                throw new \Exception("Value not allow newline char: $str");
            } else {
                if ($quote) {
                    $var .= $char;
                } else {
                    $code .= $char;
                }
            }
        }
        if ($quote) {
            return [
                'continue' => true, 'code' => $code, 'pos' => $len, 'vars' => $vars,
                'var' => $var, 'quote' => $quote
            ];
        } else {
            return ['continue' => false, 'code' => $code, 'pos' => $pos, 'vars' => $vars];
        }
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
            if (preg_match_all('/(<'.$tag.'|<\/'.$tag.'>)/', $str, $matchs, PREG_OFFSET_CAPTURE)) {
                $pos = 0;
                foreach ($matchs[0] as $match) {                    
                    $tmp  = substr($str, $pos, $match[1] - $pos);
                    $res .= $tmp;
                    $pos  = strlen($match[0]) + $match[1];
                    if ($match[0][1] !== '/') {
                        $res .= $match[0];
                        $end[$i]['num']++;
                    } else {
                        if ($end[$i]['num'] > 0) {
                            $res .= $match[0];
                            $end[$i]['num']--;
                        } else {
                            if ($tag !== self::$config['blank_tag']) {
                                $res .= $match[0].PHP_EOL.($blank ? $blank : self::readLeftBlank($tmp));
                            }
                            $res .= '<?php '.str_pad('', $end[$i]['count'], '}').' ?>';
                            array_pop($end);
                            break;
                        }
                    }
                }
                $str = substr($str, $pos);
            } else {
                $res .= $str;
                break;
            }
        } while ($i > 0);
        return $res;
    }
    
    /*
     * 读取语句结构
     */
    protected static function readStructure($structure, $val = null)
    {
        $end = true;
        $code = '';
        if ($val) $ret = self::readAttrValue($val);
        switch ($structure) {
            case 'if':
                $code = 'if ('.self::readExp($ret['code'], $ret['vars'], self::$operators).') {';
                break;
            case 'elseif':
                $code = 'elseif ('.self::readExp($ret['code'], $ret['vars'], self::$operators).') {';
                break;
            case 'else':
                $code = 'else {';
                break;
            case 'each':
                $pairs = explode(' in ', $ret['code']);
                if (count($pairs) == 2) {
                    $argument = self::readArgument($pairs[1], $ret['vars']);
                    if ($argument['type'] === 'mixed') {
                        $kv = explode(':', $pairs[0]);
                        $ct = count($kv);
                        if ($ct == 1) {
                            $code = '$'.$kv[0];
                        } elseif ($ct == 2) {
                            if (empty($kv[0])) {
                                if (self::isVarnameChars($kv[1])) {
                                    $code = '$'.$kv[1];
                                }
                            } elseif (empty($kv[1])) {
                                if (self::isVarnameChars($kv[0])) {
                                    $code = '$'.$kv[0].' => $_';
                                }
                            } else {
                                if (self::isVarnameChars($kv[0]) && self::isVarnameChars($kv[1])) {
                                    $code = '$'.$kv[0].' => $'.$kv[1];
                                }
                            }
                        }
                    }
                }
                if(isset($code)) {
                    $code = 'foreach('.$argument['value'].' as '.$code.') {';
                    break;
                }
                throw new \Exception('Read each structure error: '.$ret['code']);
            case 'for':
                if (substr_count($ret['code'], ';') === 2) {
                    $for_operator = self::$operators;
                    $for_operator[] = ';';
                    $code = 'for ('.self::readExp($ret['code'], $ret['vars'], $for_operator).') {';
                    break;
                }
                throw new \Exception('Read for structure error: '.$ret['code']);
            default:
                throw new \Exception('Read for structure error: '.$structure);
        }
        return ['code' => $code, 'end' => $end];
    }
    
    
    /*
     * 读取语句单元
     */
    protected static function readUnit($str, $vars)
    {
        $i    = 0;
        $code = '';
        $prev = null;
        $len  = strlen($str);
        while ($i < $len) {
            $unit = self::readWord(substr($str, $i));
            $i += $unit['seek'];
            if (empty($unit['end'])) {
                if (empty($code)) {
                    return self::replaceVar($unit['code']);
                } elseif(!empty($unit['code'])) {
                    return $code.'[\''.$unit['code'].'\']';
                }
                return $code;
            }
            switch ($unit['end']) {
                case '?':
                    $arr = explode(':', substr($str, $i));
                    if (empty($code)) {
                        $code = self::replaceVar($unit['code']);
                    } else {
                        $code .= '[\''.$unit['code'].'\']';
                    }
                    return $code.' ? '.self::readUnit($arr[0], $vars). ':' .self::readUnit($arr[1], $vars);
                case '[':
                    if (empty($unit['code'])) {
                        if ($prev === '.' || empty($code)) {
                            throw new \Exception('read error');
                        }
                    }
                    $pos = self::findEndPos(substr($str, $i), '[', ']');
                    $argument = self::readArgument(substr($str, $i, $pos), $vars);
                    $i += $pos + 1;
                    if(in_array($argument['type'], ['mixed', 'number', 'string'])) {
                        if ($code) {
                            if ($unit['code']) {
                                $code .= '[\''.$unit['code'].'\']';
                            }
                            $code .= '['.$argument['value'].']';
                        } else {
                            $code = self::replaceVar($unit['code']).'['.$argument['value'].']';
                        }
                    } else {
                        throw new \Exception('read_unit error :'.$str);
                    }
                    $prev = '[';
                    break;
                case '(':
                    if (empty($unit['code'])) {
                        if (empty($code) && $str[$len - 1] === ')') {
                            $argument = self::readArgument(substr($str, $i, -1), $vars);
                            if ($argument['type'] === 'string') {
                                return $argument['value'];
                            }
                        }
                        throw new \Exception('read_unit error: '.$str);
                    }
                    $arguments = $code ? [$code] :[];
                    $pos = self::findEndPos(substr($str, $i), '(', ')');
                    $args_str = trim(substr($str, $i, $pos));
                    if (!empty($args_str)) {
                        $args = explode(',', substr($str, $i, $pos));
                        foreach ($args as $arg) {
                            $arguments[] = self::readArgument($arg, $vars)['value'];
                        }
                    }
                    $i += $pos + 1;
                    $code = self::replaceFunction($unit['code'], $arguments);
                    $prev = '(';
                    break;
                case '.':
                    if (($prev === '.' || empty($code)) && empty($unit['code'])) {
                        throw new \Exception('read_unit error: '.$str);
                    }
                    if (empty($code)) {
                        $code = self::replaceVar($unit['code']);
                    } elseif (!empty($unit['code'])){
                        $code .= '[\''.$unit['code'].'\']'; 
                    }
                    $prev = '.';
                    break;
                case '|':
                    if (empty($code)) {
                        return self::replaceVar($unit['code']);
                    } else {
                        return $code.'[\''.$unit['code'].'\']';
                    }
                    break;
            }
        }
        return $code;
    }
    
    /*
     * 读取表达式
     */
    protected static function readExp($str, $vars, $exp)
    {
        $exp = array_map(function ($v) {
            return preg_quote($v);
        } , $exp);
        if (preg_match_all('#('.implode('|', $exp).')#', $str, $matchs, PREG_OFFSET_CAPTURE)) {
            $code = '';
            $start_pos = 0;
            foreach ($matchs[0] as $match) {
                if ($match[1] > $start_pos) {
                    $tmp = substr($str, $start_pos, $match[1]-$start_pos);
                    $ret = self::readBlank($tmp);
                    $code .= $ret['left'].self::readArgument($ret['str'], $vars)['value'].$ret['right'];
                }
                $code .= $match[0];
                $start_pos = strlen($match[0])+$match[1];
            }
            if ($start_pos < strlen($str)) {
                $ret = self::readBlank(substr($str, $start_pos));
                $code .= $ret['left'].self::readArgument($ret['str'], $vars)['value'].$ret['right'];
            }
            return $code;
        }
        return self::readArgument($str, $vars)['value'];
    }
    
    /*
     * 读取关键字
     */
    protected static function readWord($str)
    {
        $code = '';
        $is_end = false;
        $len = strlen($str);
        for($i = 0; $i < $len; $i++) {
            $c = $str[$i];
            if (self::isVarnameChar($c)) {
                if ($is_end || (!$code && is_numeric($c))) {
                    throw new \Exception("readWord error: $str");
                }
                $code .= $c;
            } else {
                if($c === '.' || $c === '[' || $c === '(') {
                    return ['code' => $code, 'seek' => $i + 1, 'end' => $c];
                } elseif (self::isBlankChar($c)) {
                    if ($code) {
                        $is_end = true;
                    }
                } else {
                    throw new \Exception('read_word error: '.$str);
                }
            }
        }
        return ['code' => $code, 'seek' => $len, 'end '=> ''];
    }
    
    /*
     * 读取参数
     */
    protected static function readArgument($str, $vars)
    {
        $str = trim($str);
        if ($str === 'true' || $str === 'false' || $str === 'null') {
            return ['type' => $str, 'value' => $str];
        } elseif (preg_match("/^\\$([1-9])$/", $str, $match)) {
            return ['type' => 'string', 'value' => $vars[$match[1] - 1]];
        } elseif (is_numeric($str)) {
            return ['type' => 'number', 'value' => $str];
        } elseif ($str{0} === '[' || $str{0} === '{') {
            return ['type' => 'array', 'value' => self::readArray($str, $vars)];
        } else {
            return ['type' => 'mixed', 'value' => self::readUnit($str, $vars)];
        }
    }
    
    /*
     * 读取空白符
     */
    protected static function readBlank($str)
    {
        $lpos = $rpos = 0;
        $left = $right = $tmp = '';
        $len = strlen($str);
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
     * 替换变量
     */
    protected static function replaceVar($var)
    {
        return self::$var_alias[$var] ?? '$'.$var;
    }
    
    /*
     * 替换函数
     */
    protected static function replaceFunction($name, $args)
    {
        if (isset(self::$functions[$name])) {
            if (count($args) > 0) {
                $replace_pairs = array();
                foreach ($args as $i => $arg) {
                    $replace_pairs['$'.$i] = $arg;
                }
                return strtr(self::$functions[$name], $replace_pairs);
            } else {
                return self::$functions[$name];
            }
        }
        return 'call_user_func('.self::$config['view_filter_method']."('$name'), ".implode(',', $args).')';
    }

    
    /*
     * 还原变量
     */ 
    protected static function restoreStrvars($code, $vars)
    {
        $replace_pairs = array();
        foreach ($vars as $i => $v) {
            $replace_pairs['$'.($i+1)] = $v;
        }
        return strtr($code, $replace_pairs);
    }
    
    /*
     * 寻找语句结束符位置
     */ 
    protected static function findEndPos($str, $start, $end)
    {
        $num = 0;
        $len = strlen($str);
        for($i=0;$i<$len;$i++) {
            $char = $str{$i};
            if ($char === $start) $num++;
            if ($char === $end) {
                if ($num === 0) {
                    return $i;
                } else {
                    $num--;
                }
            }
        }
        throw new \Exception("findEndPos: $str");
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
            ($ascii = ord($char)) === 95
            || ($ascii > 47 && $ascii < 58)
            || ($ascii > 64 && $ascii < 91)
            || ($ascii > 96 && $ascii < 123)
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
}
Template::init();
