<?php
namespace framework\core;

class Template
{
    protected static $init;
    // 
    protected static $operators             = [
        '!', '&', '|', '=', '>', '<', '+', '-', '*', '/', '%', '?', ':'
    ];
    // 
    protected static $structures            = [
        'if', 'elseif', 'else', 'each', 'for'
    ];
    // 
    protected static $struct_html_tag       = 'php';
    // 
    protected static $tag_struct_prefix     = '@';
    // 
    protected static $tag_assign_prefix     = '$';
    // 默认是否转义文本
    protected static $defalut_escape_text   = true;
    // 文本转义符号与反转义符号
    protected static $text_escape_sign      = ['&', '!'];
    // 文本插入左右边界符号
    protected static $text_border_sign      = ['{{', '}}'];
    // 
    protected static $view_include_method   = View::class.'::path';
    // 
    protected static $view_extends_method   = View::class.'::extends';
    // 内置函数
    protected static $functions             = [
        'is'        => '("is_$1")($0)',
        'has'       => 'isset($0)',
        'default'   => '$0 ?? $1',
        
        'str'       => 'strval($0)',
        'num'       => '($0+0)',
        'replace'   => 'str_replace($1, $2, $0)',
        'substr'    => 'substr($0, $1, $2)',
        'length'    => 'strlen($0)',
        'count'     => 'count($0)',
        'lower'     => 'strtolower($0)',
        'upper'     => 'strtoupper($0)',
        'ucfirst'   => 'ucfirst($0)',
        'trim'      => 'trim($0)',
        'index'     => 'strpos($1, $0)',
        'char'      => '$0[$1]',
        'escape'    => 'htmlspecialchars($0)',
        'unescape'  => 'htmlspecialchars_decode($0)',
        'urlencode' => 'urlencode($0)',
        'urldecode' => 'urldecode($0)',

        'split'     => 'explode($1, $0)',
        'join'      => 'implode($1, $0)',
        'keys'      => 'array_keys($0)',
        'values'    => 'array_values($0)',
        
        'abs'       => 'abs($0)',
        'floor'     => 'floor($0)',
        'round'     => 'round($0)',
        'ceil'      => 'ceil($0)',
        'rand'      => 'rand($0, $1)',
        'number'    => 'number_format($0)',
    
        'time'      => 'time()',
        'date'      => 'date($1, $0)',
    ];
    
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::get('template')) {
            
        }
    }
    
    public static function complie($str)
    {
        $str = trim($str);
        if ($extends = self::readExtendsTag($str)) {
            return $extends;
        }
        return self::textParse(self::tagParse($str));
    }
    
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
        $pairs = explode(self::$text_border_sign[0], $str);
        if (($count = count($pairs)) < 2) {
            return $str;
        }
        $res = $pairs[0];
        while ($i < $count) {
            $escape = self::$defalut_escape_text;
            if (in_array($c = $pairs[$i][0], self::$text_escape_sign)) {
                $pairs[$i] = substr($pairs[$i], 1);
                $escape = !array_search($c, self::$text_escape_sign);
            }
            $ret = ['continue' => false];
            do {
                if ($i < $count) {
                    $pair = $pairs[$i];
                    $ret  = self::readTagAttr(
                        $pair,
                        self::$text_border_sign[0],
                        self::$text_border_sign[1],
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
                $res .= substr($pair, $ret['pos']+1);
            }
        }
        return $res;
    }
    
    /*
     * 模版标签语法解析
     */
    public static function tagParse($str)
    {
        if (preg_match_all('/<%include +name *= *"([\w|\/|-]+)" *\/>/', $str, $include_matchs, PREG_OFFSET_CAPTURE)) {
            $pos = 0;
            $tmp = '';
            foreach ($include_matchs[0] as $i => $match) {
                $tmp .= substr($str, $pos, $match[1] - $pos);
                $tmp .= '<?php include '.self::$view_include_memthod.'("'.$include_matchs[1][$i][0].'", __DIR__); ?>';
                $pos = strlen($match[0]) + $match[1];
            }
            $str = $tmp.substr($str, $pos);
        }
        $regex = "/<(\w+) +".preg_quote(self::$tag_struct_prefix)."(".implode('|', self::$structures).").+/";
        if (!preg_match_all($regex, $str, $matchs, PREG_OFFSET_CAPTURE)) {
            return $str;
        }
        $res       = '';
        $pos       = 0;
        $end_tags  = [];
        $end_count = [];
        $skip_num  = [];
        foreach ($matchs[0] as $i => $match) {
            if ($pos > $match[1]) {
                throw new \Exception("Tag parse error");
            }
            $left  = substr($str, $pos, $match[1] - $pos);
            $blank = self::readLeftBlank($left);
            $res  .= $end_tags ? self::completeEndTag($left, $end_tags, $end_count, $skip_num) : $left;
        
            $ret = self::readTagAttr($match[0], $start = '<', $end = '>');
            $tag = self::readTag($ret['code'].'>', $ret['vars']);
            $res  .= implode(PHP_EOL.$blank, $tag['code']);
            if ($matchs[1][$i][0] !== self::$struct_html_tag) {
                $res .= PHP_EOL.$blank.$tag['html'];
            }
            if ($tag['end']) {
                $skip_num[] = 0;
                $end_tags[] = $matchs[1][$i][0];
                $end_count[] = $tag['count'];
                if (substr($tag['html'], -2, 1) === '/') {
                    $res .= PHP_EOL.$blank.str_pad('<?php ', $tag['count'], '}').' ?>';
                } else {
                    $skip_num[] = 0;
                    $end_count[] = $tag['count'];
                    $end_tags[] = $matchs[1][$i][0];
                }
                if (strlen($match[0]) - $ret['pos'] > 2) {
                    $end_html = substr($match[0], $ret['pos']+1);
                    $res .= $end_tags ? self::completeEndTag($end_html, $end_tags, $end_count, $skip_num, $blank) :$end_html;
                }
                $pos = strlen($match[0])+$match[1];
            }
        }
        $res .= $end_tags ? self::completeEndTag(substr($str, $pos), $end_tags, $end_count, $skip_num) : substr($str, $pos);
        return $res;
    }
    
    /*
     * 合并布局模版
     */
    public static function mergeExtends($self, $parent)
    {
        $res = self::readExtendsTag($self, true).PHP_EOL;
        $s = '/<block +name *= *"(\w+)" *>/';
        $e = '</block>';
        if (preg_match_all($s, $self, $self_matchs, PREG_OFFSET_CAPTURE)) {
            $pos = 0;
            $sub_blocks = [];
            foreach ($self_matchs[0] as $i => $match) {
                if ($match[1] >= $pos) {
                    $pos = stripos($self, $e, $match[1]);
                    if ($pos) {
                        $start = $match[1] + strlen($match[0]);
                        $sub_blocks[$self_matchs[1][$i][0]] = substr($self, $start, $pos - $start);
                        continue;
                    }
                }
                throw new \Exception('extend_merge error');
            }
        }

        if (preg_match_all($s, $parent, $parent_matchs, PREG_OFFSET_CAPTURE)) {
            $s_pos = 0;
            $e_pos = 0;
            foreach ($parent_matchs[0] as $i => $match) {
                if ($match[1] >= $e_pos) {
                    $res .= substr($parent, $s_pos, $match[1] - $s_pos);
                    $e_pos = stripos($parent, $e, $match[1]);
                    if ($e_pos) {
                        $block_name = $parent_matchs[1][$i][0];
                        if (isset($sub_blocks[$block_name])) {
                            $res .= $sub_blocks[$block_name];
                        } else {
                            $start = $match[1] + strlen($match[0]);
                            $res .= substr($parent, $start, $e_pos - $start);
                        }
                        $s_pos = $e_pos + strlen($e);
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
    
    protected static function readExtendsTag($str, $check = false)
    {
        if (preg_match('/^<extends +name *= *"([\w|\/|-]+)" *\/>/', $str, $extends_match)) {
            return sprintf(
                '<?php if ($_f = %s("%s", __FILE__, %s)) return include $_f; ?>',
                self::$view_extends_method,
                $extends_match[1],
                $check ? 'true' : 'false'
            );
        }
    }
    
    protected static function readTag($tag, $vars)
    {
        $end  = false;
        $html = '';
        $code = [];
        $has_noas_attr = false;
        $reg = "/".self::$tag_struct_prefix."(".implode('|', self::$structures).")(=\\$[1-9])?/";
        if (preg_match_all($reg, $tag, $matchs, PREG_OFFSET_CAPTURE)) {
            $start_pos = 0;
            foreach ($matchs[1] as $i => $attr) {
                $tmp = trim(substr($tag, $start_pos, $matchs[0][$i][1] - $start_pos));
                if (!empty($tmp)) {
                    $html .= $tmp;
                }
                if ($attr[0] === 'else') {
                    $val = null;
                } else {
                    if (empty($matchs[2][$i][0])) {
                        throw new \Exception('read_tag error: '.$tag);
                    }
                    $val = substr(trim($vars[$matchs[2][$i][0][2]-1]), 1, -1);
                }
                $attr_ret = self::readStructure($attr[0], $val);
                $code[] = '<?php '.$attr_ret['code'].' ?>';
                if(!$end) {
                    $end = $attr_ret['end'];
                }
                $start_pos = $matchs[0][$i][1] + strlen($matchs[0][$i][0]);
            }
            $html .= substr($tag, $start_pos);
        }
        if ($vars) {
            $html = self::restoreStrvars($html, $vars);
        }
        return [
            'html' => $html, 'code' => $code, 'end' => $end, 'count' => $i + 1
        ];
    }
    
    public static function readTagAttr($str, $start = null, $end = null, $continue = null)
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
            return ['continue' => true, 'code' => $code, 'pos' => $len, 'vars' => $vars, 'var' => $var, 'quote' => $quote];
        } else {
            return ['continue' => false, 'code' => $code, 'pos' => $pos, 'vars' => $vars];
        }
    }
    
    /*
     * 合并完成模版标签闭合
     */
    protected static function completeEndTag($str, &$end_tags, &$end_count, &$skip_num, $blank = null)
    {
        $res = '';
        do {
            $i = count($end_tags)-1;
            $start_tag = '<'.$end_tags[$i];
            $end_tag = '<\/'.$end_tags[$i].'>';
            if (preg_match_all('/('.$start_tag.'|'.$end_tag.')/', $str, $matchs, PREG_OFFSET_CAPTURE)) {
                $start_pos = 0;
                foreach ($matchs[0] as $match) {                    
                    $tmp = substr($str, $start_pos, $match[1] - $start_pos);
                    $res .= $tmp;
                    $start_pos = strlen($match[0]) + $match[1];
                    if ($match[0] === $start_tag) {
                        $res .= $match[0];
                        $skip_num[$i]++;
                    } else {
                        if ($skip_num[$i] > 0) {
                            $res .= $match[0];
                            $skip_num[$i]--;
                        } else {
                            if ($end_tags[$i] !== self::$struct_html_tag) {
                                $res .= $match[0].PHP_EOL.($blank ? $blank : self::readLeftBlank($tmp));
                            }
                            $res .= '<?php '.str_pad('', $end_count[$i], '}').' ?>';
                            array_pop($skip_num);
                            array_pop($end_tags);
                            array_pop($end_count);
                            break;
                        }
                    }
                }
                $str = substr($str, $start_pos);
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
        if ($val) $ret = self::readTagAttr($val);
        switch ($structure) {
            case 'if':
                $code = 'if ('.self::readExp($ret['code'], $ret['vars'], self::$operators).') {';
                break;
            case 'elseif':
                $code = ' elseif ('.self::readExp($ret['code'], $ret['vars'], self::$operators).') {';
                break;
            case 'else':
                $code = ' else {';
                break;
            case 'each':
                $pairs = explode(' as ', $ret['code']);
                if (count($pairs) === 2) {
                    $argument = self::readArgument($pairs[0], $ret['vars']);
                    if ($argument['type'] === 'mixed') {
                        $list = explode(' ', trim($pairs[1]), 2);
                        if (count($list) === 1) {
                            if (self::isVarnameChars($list[0])) {
                                $code = ' foreach('.$argument['value'].' as $'.$list[0].') {';
                            }
                        } else {
                            if (self::isVarnameChars($list[0]) && self::isVarnameChars($list[1])) {
                                $code = ' foreach('.$argument['value'].' as $'.$list[0].' => $'.$list[1].') {';
                            }
                        }
                    }
                }
                if(empty($code)) {
                    throw new \Exception('read_structure error: '.$ret['code']);
                }
                break;
            case 'for':
                if (substr_count($ret['code'], ';') === 2) {
                    $for_operator = self::$operators;
                    $for_operator[] = ';';
                    $code = 'for ('.self::readExp($ret['code'], $ret['vars'], $for_operator).') {';
                } else {
                    throw new \Exception('read_structure error: '.$ret['code']);
                }
                break;
            default:
                throw new \Exception('read_structure error: '.$structure);
        }
        return ['code' => $code, 'end' => $end];
    }
    
    
    /*
     * 读取语句单元
     */
    protected static function readUnit($str, $vars)
    {
        $i = 0;
        $code = '';
        $prev = null;
        $len = strlen($str);
        while ($i < $len) {
            $unit = self::readWord(substr($str, $i));
            $i += $unit['seek'];
            if (empty($unit['end'])) {
                //continue;
                if (empty($code)) {
                    return self::replaceVar($unit['code']);
                } else {
                    return $code.'[\''.$unit['code'].'\']';
                }
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
                        if ($prev === '.' || empty($code)) throw new \Exception('read error');
                    }
                    $pos = self::findEndPos(substr($str, $i), '[', ']');
                    $argument = self::readArgument(substr($str, $i, $pos), $vars);
                    $i += $pos+1;
                    if($argument['type'] === 'mixed' || $argument['type'] === 'number' || $argument['type'] === 'string') {
                        if ($code) {
                            if ($unit['code']) $code .= '[\''.$unit['code'].'\']';
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
                        if (empty($code) && $str{$len-1} === ')') {
                            $argument = self::readArgument(substr($str, $i, -1), $vars);
                            if ($argument['type'] === 'string') {
                                return $argument['value'];
                            }
                        }
                        throw new \Exception('read_unit error: '.$str);
                    }
                    $arguments = $code ? array($code) : array();
                    $pos = self::findEndPos(substr($str, $i), '(', ')');
                    $args_str = trim(substr($str, $i, $pos));
                    if (!empty($args_str)) {
                        $args = explode(',', substr($str, $i, $pos));
                        foreach ($args as $arg) {
                            $arguments[] = self::readArgument($arg, $vars)['value'];
                        }
                    }
                    $i += $pos+1;
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
                case '':
                    if (empty($code)) {
                        return self::replaceVar($unit['code']);
                    } else {
                        return $code.'[\''.$unit['code'].'\']';
                    }
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
        for($i=0; $i<$len; $i++) {
            $c = $str{$i};
            if (self::isVarnameChar($c)) {
                if ($is_end || (!$code && is_numeric($c))) throw new \Exception('read_word error: '.$str);
                $code .= $c;
            } else {
                if($c === '.' || $c === '[' || $c === '(') {
                    return array('code'=> $code, 'seek'=>$i+1, 'end'=>$c);
                } elseif (self::isBlankChar($c)) {
                    if($code) $is_end = true;
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
            return array('type'=>$str, 'value'=>$str);
        } elseif (preg_match("/^\\$([1-9])$/", $str, $match)) {
            return array('type'=>'string', 'value'=>$vars[$match[1]-1]);
        } elseif (is_numeric($str)) {
            return array('type'=>'number', 'value'=>$str);
        } elseif ($str{0} === '[' || $str{0} === '{') {
            return array('type'=>'array', 'value'=>self::readArray($str, $vars));
        } else {
            return array('type'=>'mixed', 'value'=>self::readUnit($str, $vars));
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
        for ($i=0; $i<$len; $i++) {
            if (self::isBlankChar($str{$i})) {
                $left .= $str{$i};
            } else {
                $lpos = $i;
                break;
            }
        }
        if (strlen($left) === $len) {
            return array('left'=>$left, 'right'=>'', 'str'=>'');
        } else {
            for ($i=$len-1; $i>=0; $i--) {
                if (self::isBlankChar($str{$i})) {
                    $right .= $str{$i};
                } else {
                    $rpos = $i;
                    break;
                }
            }
            return array('left' => $left, 'right' => $right, 'str'=> substr($str, $lpos, $rpos - $lpos + 1));
        }
    }
    
    /*
     * 读取左边空白符
     */
    protected static function readLeftBlank($str)
    {
        $blank = '';
        $i = strlen($str)-1;
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
        throw new \Exception('replaceFunction: '.$name);
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
        throw new \Exception('find_end_pos: '.$str);
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
        $ascii = ord($char);
        return ($ascii === 95 || ($ascii > 47 && $ascii < 58) || ($ascii > 64 && $ascii < 91) || ($ascii > 96 && $ascii < 123));
    }
    
    /*
     * 是否变量名字符串
     */ 
    protected static function isVarnameChars($str)
    {
        $len = strlen($str);
        if($len > 0 && self::isVarnameChar($str[0]) && !is_numeric($str[0])) {
            for ($i = 1; $i < $len; $i++) {
                if (!self::isVarnameChar($str[$i])) return false;
            }
            return true;
        }
        return false;
    }
}
