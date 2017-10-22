<?php
namespace framework\core;

class Template
{
    private static $init;
    
    //private $blank_tag = 'php';
    //private $tag_attr_prefix = 'php-';
    protected static $ebp = ['{$', '}'];
    
    protected static $sbp = ['{{', '}}'];
    
    protected static $operator = [
        '!', '&', '|', '=', '>', '<', '+', '-', '*', '/', '%', '?', ':'
    ];
    
    protected static $structure = [
        'eq', 'if', 'elseif', 'else', 'switch', 'case', 'default', 'each', 'for', 'import', 'layout'
    ];
    
    protected static $var_alias = [
        'get'       => '_GET',
        'post'      => '_POST',
        'cookie'    => '_COOKIE',
        'seesion'   => '_SEESION',
        'server'    => '_SERVER',
        'request'   => '_REQUEST'
    ];
    
    protected static $function_alias = [
        'is'        => 'call_user_func("is_".$1, $0)',
        'has'       => 'isset($0)',
        'default'   => 'isset($0) ? $0 : $1',
        
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
        'char'      => '$0{$1}',
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
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('template');
        if (isset($config['function_alias'])) {
            self::$function_alias = array_merge(self::$function_alias, $config['function_alias']);
        }
    }
    
    public static function complie($template, $layout = null)
    {
        if (isset(self::$sbp)) {
            $template = self::ebpParser(self::sbpParser($template));
        } else {
            $template = self::ebpParser(self::tagParser($template));
        }
        return $layout ? self::layoutMerge($template, $layout) : $template;
    }

    protected static function ebpParser($str)
    {
        $i = 1;
        $pairs = explode(self::$ebp[0], $str);
        $tpl = $pairs[0];
        $count = count($pairs);
        if ($count > 1) {
            while ($i < $count) {
                $fchar = $pairs[$i]{0};
                if ($fchar === '$') {
                    $pairs[$i] = substr($pairs[$i], 1);
                }
                $ret = array('continue' => false);
                do {
                    if ($i < $count) {
                        $pair = $pairs[$i];
                        $ret = self::replaceStrvars($pair, self::$ebp[0], self::$ebp[1], $ret['continue'] ? $ret : null);
                        $i++;
                    } else {
                        throw new \Exception('read error: '.$count);
                    }
                } while ($ret['continue'] === true);
                if ($fchar === '$') {
                    $tpl .= '<?php echo htmlspecialchars('.self::readUnit($ret['code'], $ret['vars']).'); ?>';
                } else {
                    $tpl .= '<?php echo '.self::readUnit($ret['code'], $ret['vars']).'; ?>';
                }
                if(strlen($pair) - $ret['pos'] > 1) $tpl .= substr($pair, $ret['pos']+1);
            }
        }
        return $tpl;
    }
    
    protected static function sbpParser($str)
    {
        $reg = "/".preg_quote(self::$sbp[0])."(".implode('|', self::$structure).").+/";
        $str = preg_replace_callback($reg, function ($match) {
            $tmp = '';
            $slen = strlen(self::$sbp[0]);
            $elen = strlen(self::$sbp[1]);
            $ret = self::replaceStrvars(substr($match[0], $slen), self::$sbp[0], self::$sbp[1]);
            if ($ret['continue']) throw new \Exception('read error: '.$match[0]);
            $val = self::restoreStrvars(substr($ret['code'], strlen($match[1])+1), $ret['vars']);
            $structure_ret = self::readStructure($match[1], $val);
            if ($match[1] == 'else' || $match[1] == 'elseif') {
                $tmp .= '<?php }'.$structure_ret['code'].' ?>';
            } else {
                $tmp .= '<?php '.$structure_ret['code'].' ?>';
            }
            if (strlen($match[0]) - $ret['pos'] > $elen + 2) {
                $tmp .= substr($match[0], $ret['pos']+$elen);
            }
            return $tmp;
        }, $str);
        $reg = "/".preg_quote(self::$sbp[0])."\/(if|each|for|switch)".preg_quote(self::$sbp[1])."/";
        $str = preg_replace($reg, '<?php } ?>', $str);
        if ($str) return $str;
        throw new \Exception('sbp_parser error: '.$str);
    }
    
    protected static function tagParser($str)
    {
        $reg = "/<([a-z]+)[ \t]+".self::$tag_attr_prefix."(".implode('|', self::$structure).").+/";
        if (preg_match_all($reg, $str, $matchs, PREG_OFFSET_CAPTURE)) {
            $tpl = '';
            $start_pos = 0;
            $end_tags = array();
            $skip_num = array();
            foreach ($matchs[0] as $i => $match) {
                $tmp = substr($str, $start_pos, $match[1]-$start_pos);
                $blank = self::readLeftBlank($tmp);
                $tpl .= $end_tags ? self::completeEndTag($tmp, $end_tags, $skip_num) : $tmp;
                $ret = self::replaceStrvars($match[0], $start = '<', $end = '>');
                $tag = self::readTag($ret['code'].'>', $ret['vars']);
                $tpl .= implode("\r\n".$blank, $tag['code']);
                if ($matchs[1][$i][0] != self::$blank_tag) {
                    $tpl .= "\r\n".$blank.$tag['html'];
                }
                if ($tag['end']) {
                    if (substr($tag['html'], -2, 1) === '/') {
                        $tpl .= "\r\n".$blank.'<?php } ?>';
                    } else {
                        $skip_num[] = 0;
                        $end_tags[] = $matchs[1][$i][0];
                    }
                }
                if (strlen($match[0]) - $ret['pos'] > 2) {
                    $end_html = substr($match[0], $ret['pos']+1);
                    $tpl .= $end_tags ? self::completeEndTag($end_html, $end_tags, $skip_num, $blank) : $end_html;
                }
                $start_pos = strlen($match[0])+$match[1];
            }
            $tpl .= $end_tags ? self::completeEndTag(substr($str, $start_pos), $end_tags, $skip_num) : substr($str, $start_pos);
            return $tpl;
        }
        return $str;
    }
    
    protected static function layoutMerge($str1, $str2)
    {
        if (isset(self::$sbp)) {
            $start_re = '/'.self::$sbp[0].'block (\w+)'.self::$sbp[1].'/';
            $end_find = self::$sbp[0].'/block'.self::$sbp[1];
        } else {
            $start_re = '/<block name="(\w+)">/';
            $end_find = '</block>';
        }
        $sub_block = array();
        if (preg_match_all($start_re, $str2, $matchs, PREG_OFFSET_CAPTURE)) {
            $end_pos = 0;
            foreach ($matchs[0] as $i => $match) {
                if ($match[1] >= $end_pos) {
                    $end_pos = stripos($str2, $end_find, $match[1]);
                    if ($end_pos) {
                        $start = $match[1]+strlen($match[0]);
                        $sub_block[$matchs[1][$i][0]] = substr($str2, $start, $end_pos-$start);
                    }else {
                        throw new \Exception('extend_merge error');
                    }
                } else {
                    throw new \Exception('extend_merge error');
                }
            }
        }
        $code = '';
        if (preg_match_all($start_re, $str1, $matchs, PREG_OFFSET_CAPTURE)) {
            $start_pos = 0;
            $end_pos = 0;
            foreach ($matchs[0] as $i => $match) {
                if ($match[1] >= $end_pos) {
                    $code .= substr($str1, $start_pos, $match[1]-$start_pos);
                    $end_pos = stripos($str1, $end_find, $match[1]);
                    if ($end_pos) {
                        $block_name = $matchs[1][$i][0];
                        if (isset($sub_block[$block_name])) {
                            $code .= $sub_block[$block_name];
                        } else {
                            $start = $match[1]+strlen($match[0]);
                            $code .= substr($str1, $start, $end_pos-$start);
                        }
                        $start_pos = $end_pos+strlen($end_find);
                    } else {
                        throw new \Exception('extend_merge error');
                    }
                } else {
                    throw new \Exception('extend_merge error');
                }
            }
            if ($start_pos < strlen($str1)) $code .= substr($str1, $start_pos);
        }
        return $code; 
    }
    
    protected static function completeEndTag($str, &$end_tags, &$skip_num, $blank = null)
    {
        $code = '';
        do {
            $i = count($end_tags)-1;
            $start_tag = '<'.$end_tags[$i];
            $end_tag = '<\/'.$end_tags[$i].'>';
            if (preg_match_all('/('.$start_tag.'|'.$end_tag.')/', $str, $matchs, PREG_OFFSET_CAPTURE)) {
                $start_pos = 0;
                foreach ($matchs[0] as $match) {                    
                    $tmp = substr($str, $start_pos, $match[1]-$start_pos);
                    $code .= $tmp;
                    $start_pos = strlen($match[0])+$match[1];
                    if ($match[0] === $start_tag) {
                        $code .= $match[0];
                        $skip_num[$i]++;
                    } else {
                        if ($skip_num[$i] > 0) {
                            $code .= $match[0];
                            $skip_num[$i]--;
                        } else {
                            if ($end_tags[$i] !== self::$blank_tag) {
                                $code .= $match[0]."\r\n".($blank ? $blank : self::readLeftBlank($tmp));
                            }
                            $code .= '<?php } ?>';
                            array_pop($skip_num);
                            array_pop($end_tags);
                            break;
                        }
                    }
                }
                $str = substr($str, $start_pos);
            } else {
                $code .= $str;
                break;
            }
        } while ($i > 0);
        return $code;
    }
    
    protected static function readTag($tag, $vars)
    {
        $end = false;
        $html = '';
        $code = array();
        $has_noas_attr = false;
        $reg = "/".self::$tag_attr_prefix."(".implode('|', self::$structure).")(=\\$[1-9])?/";
        if (preg_match_all($reg, $tag, $matchs, PREG_OFFSET_CAPTURE)) {
            $start_pos = 0;
            foreach ($matchs[1] as $i => $attr) {
                $tmp = trim(substr($tag, $start_pos, $matchs[0][$i][1]-$start_pos));
                if (!empty($tmp)) $html .= $tmp;
                if ($attr[0] !== 'as') {
                    if ($has_noas_attr) throw new \Exception('read_tag error: '.$tag);
                    $has_noas_attr = true;
                }
                if ($attr[0] === 'else' || $attr[0] === 'default') {
                    $val = null;
                } else {
                    if (empty($matchs[2][$i][0])) {
                        throw new \Exception('read_tag error: '.$tag);
                    }
                    $val = substr(trim($vars[$matchs[2][$i][0]{2}-1]), 1, -1);
                }
                $attr_ret = self::readStructure($attr[0], $val);
                $code[] = '<?php '.$attr_ret['code'].' ?>';
                if(!$end) $end = $attr_ret['end'];
                $start_pos = $matchs[0][$i][1]+strlen($matchs[0][$i][0]);
            }
            $html .= substr($tag, $start_pos);
        }
        if ($vars) $html = self::restoreStrvars($html, $vars);
        return array('html'=>$html, 'code'=>$code, 'end'=>$end);
    }
    
    protected static function readStructure($structure, $val = null)
    {
        $end = false;
        $code = '';
        if ($val) $ret = self::replaceStrvars($val);
        switch ($structure) {
            case 'eq':
                $pairs = explode(';', $ret['code']);
                foreach ($pairs as $pair) {
                    $item = explode('=', trim($pair));
                    if (count($item) !== 2) throw new \Exception('read_structure error: '.$pair);
                    $kvar = self::readArgument(trim($item[0]), $ret['vars']);
                    $vvar = self::readArgument(trim($item[1]), $ret['vars']);
                    $code .= $kvar['value'].' = '.$vvar['value'].';';
                }
                break;
            case 'if':
                $end = true;
                $code = 'if ('.self::readExp($ret['code'], $ret['vars'], self::$operator).') {';
                break;
            case 'elseif':
                $end = true;
                $code = ' elseif ('.self::readExp($ret['code'], $ret['vars'], self::$operator).') {';
                break;
            case 'else':
                $end = true;
                $code = ' else {';
                break;
            case 'switch':
                $end = true;
                $argument = self::readArgument($ret['code'], $ret['vars']);
                $code =  'switch ('.$argument['value'].') {';
                break;
            case 'case':
                $argument = self::readArgument($ret['code'], $ret['vars']);
                $code = 'case: '.$argument['value'];
                break;
            case 'default':
                $code = 'default: ';
                break;
            case 'each':
                $end = true;
                $pairs = explode(' as ', $ret['code']);
                if (count($pairs) === 2) {
                    $argument = self::readArgument($pairs[0], $ret['vars']);
                    if ($argument['type'] === 'mixed') {
                        $list = explode(' ', trim($pairs[1]), 2);
                        if (count($list) === 1) {
                            if (self::isVarnameChars($list[0])) {
                                $code = 'foreach( '.$argument['value'].' as $'.$list[0].') {';
                            }
                        } else {
                            if (self::isVarnameChars($list[0]) && self::isVarnameChars($list[1])) {
                                $code = 'foreach( '.$argument['value'].' as $'.$list[0].' => $'.$list[1].') {';
                            }
                        }
                    }
                }
                if(empty($code)) throw new \Exception('read_structure error: '.$ret['code']);
                break;
            case 'for':
                $end = true;
                if (substr_count($ret['code'], ';') === 2) {
                    $for_operator = self::$operator;
                    $for_operator[] = ';';
                    $code = 'for ('.self::readExp($ret['code'], $ret['vars'], $for_operator).') {';
                } else {
                    throw new \Exception('read_structure error: '.$ret['code']);
                }
                break;
            case 'import':
                $argument = self::readArgument($ret['code'], $ret['vars']);
                $code = 'include '.View::class.'::file('.$argument['value'].', __DIR__);';
                break;
            case 'layout':
                $argument = self::readArgument($ret['code'], $ret['vars']);
                $code = 'if ('.View::class.'::layout('.$argument['value'].', __FILE__)) return;';
                break;    
            default:
                throw new \Exception('read_structure error: '.$structure);
        }
        return array('code'=>$code, 'end'=>$end);
    }
    
    protected static function readUnit($str, $vars)
    {
        $i = 0;
        $code = '';
        $prev = null;
        $len = strlen($str);
        while ($i < $len) {
            $unit = self::readWord(substr($str, $i));
            $i += $unit['seek'];
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
    
    
    protected static function readJson($str)
    {
        $quote = null;
        $len = strlen($str);
        $start_char = $str{0};
        $start_char_num = 0;
        if ($start_char === '{') {
            $end_char = '}';
        } elseif ($start_char === '[') {
            $end_char = ']';
        } else {
            return false;
        }
        $code = $start_char;
        for ($i=1; $i<$len; $i++) {
            $char = $str{$i};
            if (self::isQuoteChar($char)) {
                if ($quote) {
                    if ($char === $quote) {
                        $quote = null;
                    }
                } else {
                    $quote = $char;
                }
            } elseif ($char === $start_char) {
                if (!$quote) {
                    $start_char_num++;
                }
            } elseif ($char === $end_char) {
                if (!$quote) {
                    if ($start_char_num > 0) {
                        $start_char_num--;
                    } else {
                        $array = json_decode($code.$end_char, true);
                        if ($array) {
                            return array('code'=>var_export($array, true), 'pos'=>$i);
                        }
                        return false;
                    }
                }
            }
            $code .= $char;
        }
        return false;
    }
    
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
        return array('code'=> $code, 'seek'=>$len, 'end'=>'');
    }
    
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
            return array('left'=>$left, 'right'=>$right, 'str'=>substr($str, $lpos, $rpos-$lpos+1));
        }
    }
    
    protected static function readLeftBlank($str)
    {
        $blank = '';
        $i = strlen($str)-1;
        while ($i >= 0) {
            if (isset($str{$i})) {
               if (self::isBlankChar($str{$i})) {
                   $blank .= $str{$i};
               } else {
                   return $blank;
               }
            }
            $i--;
        }
    }
    
    protected static function replaceVar($var)
    {
        if (isset(self::$var_alias[$var])) {
            return '$'.self::$var_alias[$var];
        } else {
            return '$'.$var;
        }
    }
    
    protected static function replaceFunction($name, $args)
    {
        if (isset(self::$function_alias[$name])) {
            if (count($args) > 0) {
                $replace_pairs = array();
                foreach ($args as $i => $arg) {
                    $replace_pairs['$'.$i] = $arg;
                }
                return strtr(self::$function_alias[$name], $replace_pairs);
            } else {
                return self::$function_alias[$name];
            }
        } else{
            return 'call_user_func($this->functions["'.$name.'"],'.implode(',', $args).')';
        }
    }
    
    protected static function replaceStrvars($str , $start = null, $end = null, $continue = null)
    {
        $pos = 0;
        $num = 0;
        if ($continue) {
            $var = $continue['var'];
            if ($start) $var .= $start;
            $code = $continue['code'];
            $quote = $continue['quote'];
            $vars = $continue['vars'];
        } else {
            $var = '';
            $code = '';
            $quote = null;
            $vars = array();
        }
        if($end) $end_len = strlen($end);
        $len = strlen($str);
        for($i=0; $i<$len; $i++) {
            $char = $str{$i};
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
            } elseif ($end && ($i-$end_len) > -2) {
                if ($quote){
                    $var .= $char;
                } elseif (substr($str, $i+1-$end_len, $end_len) !== $end) {
                    $code .= $char;
                } else {
                    if ($end_len > 1) $code = substr($code, 0, 1-$end_len);
                    return array('continue'=>false, 'code'=>$code, 'pos'=>$i, 'vars'=>$vars);
                }
            } elseif (self::isNewlineChar($char)) {
                throw new \Exception('replace_strvars error: '.$str);
            } else {
                if ($quote) {
                    $var .= $char;
                } else {
                    $code .= $char;
                }
            }
        }
        if ($quote) {
            return array('continue'=>true, 'code'=>$code, 'var'=>$var, 'pos'=>$len, 'vars'=>$vars, 'quote'=>$quote);
        } else {
            return array('continue'=>false, 'code'=>$code, 'pos'=>$pos, 'vars'=>$vars);
        }
    }
    
    protected static function restoreStrvars($code, $vars)
    {
        $replace_pairs = array();
        foreach ($vars as $i => $v) {
            $replace_pairs['$'.($i+1)] = $v;
        }
        return strtr($code, $replace_pairs);
    }
    
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
     
    protected static function isQuoteChar($char)
    {
        return $char === '"' || $char === "'";
    }    
    
    protected static function isBlankChar($char)
    {
        return $char === ' ' || $char === "\t";
    }
    
    protected static function isNewlineChar($char)
    {
        return $char === "\r" || $char === "\n";
    }
    
    protected static function isVarnameChar($char)
    {
        $ascii = ord($char);
        return ($ascii === 95 || ($ascii > 47 && $ascii < 58) || ($ascii > 64 && $ascii < 91) || ($ascii > 96 && $ascii < 123));
    }
    
    protected static function isVarnameChars($str)
    {
        $len = strlen($str);
        if($len > 0 && self::isVarnameChar($str{0}) && !is_numeric($str{0})) {
            for ($i=1; $i<$len; $i++) {
                if (!self::isVarnameChar($str{$i})) return false;
            }
            return true;
        }
        return false;
    }
}
