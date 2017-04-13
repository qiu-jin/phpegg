<?php
namespace Framework\Extend\View;

class Template
{
    private $blank_tag = 'php';
    private $tag_attr_prefix = 'php-';
    private $ebp = array('{$', '}');
    private $sbp = array('{{', '}}');
    private $operator = array(
        '!', '&', '|', '=', '>', '<', '+', '-', '*', '/', '%', '?', ':'
    );
    private $structure = array(
        'as', 'if', 'elseif', 'else', 'switch', 'case', 'default', 'each', 'for', 'include'
    );
    private $var_alias = array(
        'get'       => '_GET',
        'post'      => '_POST',
        'cookie'    => '_COOKIE',
        'seesion'   => '_SEESION',
        'server'    => '_SERVER',
        'request'   => '_REQUEST'
    );
    private $alias_function = array(
        'is'        => 'in_array(strtolower($1),array("string","numeric","null","bool","array","object")) ? call_user_func("is_".$1,$0) : false',
        'has'       => 'isset($0)',
        
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
        'numformat' => 'number_format($0)',
    
        'time'      => 'time()',
        'date'      => 'date($1, $0)',
        
    );
    private $render_function = array();
    
    private $allow_source_function = false;
    

    public function __construct($config = null)
    {

    }
    
    public function complie($str, $extend = null)
    {
        if ($extend) {
            $str = $this->extend_merge($str, $extend);
        } else {
            $extend = $this->extend_prepare($str);
            if ($extend !== false) return $extend;
        }
        if (isset($this->sbp)) {
            return $this->ebp_parser($this->sbp_parser($str));
        } else {
            return $this->ebp_parser($this->tag_parser($str));
        }
    }
    
    private function extend_prepare($str) 
    {
        if (isset($this->sbp)) {
            $re = "/".$this->sbp[0]."extend[ \t]+.+/";
        } else {
            $re = "/<extend[ \t]+.+[ \t]*>/";
        }
        if (preg_match($re, $str, $match, PREG_OFFSET_CAPTURE)) {

            
            $ret = $this->replace_strvars($match[0][0], $this->sbp[0], $this->sbp[1]);
            $structure_ret = $this->read_structure('extend', $ret['vars']);
            
            print_r($match);
            print_r($ret);
            die;

            return '<?php $this->_extend("'.$match[1][0].'", __FILE__, "'.base64_encode(substr($str, $match[0][1]+strlen($match[0][0]))).'"); ?>';
        }
        die('extend_prepare');
        return false;
    }
    
    
    private function _layout_prepare($str) 
    {
        $re = isset($this->sbp) ?  '/{{layout (\w+)}}/' : '/<layout name="(\w+)" />/';
        if (preg_match($re, $str, $match, PREG_OFFSET_CAPTURE)) {
            if ($match[0][1] > 0 && trim(substr($str,0, $match[0][1])) !== '') {
                throw new \Exception('sub_parser error');
            }
            if (isset($this->sbp)) {
                $start_re = '/'.$this->sbp[0].'block (\w+)'.$this->sbp[1].'/';
                $end_find = $this->sbp[0].'/block'.$this->sbp[1];
            } else {
                $start_re = '/<block name="(\w+)">/';
                $end_find = '</block>';
            }
            if (preg_match_all($start_re, $extend, $matchs, PREG_OFFSET_CAPTURE)) {
                $end_pos = 0;
                foreach ($matchs[0] as $i => $match) {
                    if ($match[1] >= $end_pos) {
                        $end_pos = stripos($extend, $end_find, $match[1]);
                        if ($end_pos) {
                            $start = $match[1]+strlen($match[0]);
                            $sub_block[$matchs[1][$i][0]] = substr($extend, $start, $end_pos-$start);
                        }else {
                            throw new \Exception('extend_merge error');
                        }
                    } else {
                        throw new \Exception('extend_merge error');
                    }
                }
            }
            $code = '<?php'.PHP_EOL;
            foreach ($blocks as $id => $block) {
                $code .= '$_blocks["'.$id.'"] = <<<EOT'.PHP_EOL.$block.PHP_EOL.'EOT;'.PHP_EOL;
            }
            $code = '$this->_layout("'.$match[1][0].'", $_blocks, __FILE__); ?>';
            
            
            //base64_encode(substr($str, $match[0][1]+strlen($match[0][0])))
        }
        return false;
    }
    
    private function _extend_prepare($str) 
    {
        $re = isset($this->sbp) ?  '/{{extend (\w+)}}/' : '/<extend name="(\w+)" />/';
        if (preg_match($re, $str, $match, PREG_OFFSET_CAPTURE)) {
            if ($match[0][1] > 0 && trim(substr($str,0, $match[0][1])) !== '') {
                throw new \Exception('sub_parser error');
            }
            return '<?php $this->_extend("'.$match[1][0].'", __FILE__, "'.base64_encode(substr($str, $match[0][1]+strlen($match[0][0]))).'"); ?>';
        }
        return false;
    }
    
    private function extend_merge($str, $extend)
    {
        if (isset($this->sbp)) {
            $start_re = '/'.$this->sbp[0].'block (\w+)'.$this->sbp[1].'/';
            $end_find = $this->sbp[0].'/block'.$this->sbp[1];
        } else {
            $start_re = '/<block name="(\w+)">/';
            $end_find = '</block>';
        }
        $sub_block = array();
        if (preg_match_all($start_re, $extend, $matchs, PREG_OFFSET_CAPTURE)) {
            $end_pos = 0;
            foreach ($matchs[0] as $i => $match) {
                if ($match[1] >= $end_pos) {
                    $end_pos = stripos($extend, $end_find, $match[1]);
                    if ($end_pos) {
                        $start = $match[1]+strlen($match[0]);
                        $sub_block[$matchs[1][$i][0]] = substr($extend, $start, $end_pos-$start);
                    }else {
                        throw new \Exception('extend_merge error');
                    }
                } else {
                    throw new \Exception('extend_merge error');
                }
            }
        }
        if (preg_match_all($start_re, $str, $matchs, PREG_OFFSET_CAPTURE)) {
            $start_pos = 0;
            $end_pos = 0;
            foreach ($matchs[0] as $i => $match) {
                if ($match[1] >= $end_pos) {
                    $code .= substr($str, $start_pos, $match[1]-$start_pos);
                    $end_pos = stripos($str, $end_find, $match[1]);
                    if ($end_pos) {
                        $block_name = $matchs[1][$i][0];
                        if (isset($sub_block[$block_name])) {
                            $code .= $sub_block[$block_name];
                        } else {
                            $start = $match[1]+strlen($match[0]);
                            $code .= substr($str, $start, $end_pos-$start);
                        }
                        $start_pos = $end_pos+strlen($end_find);
                    } else {
                        throw new \Exception('extend_merge error');
                    }
                } else {
                    throw new \Exception('extend_merge error');
                }
            }
            if ($start_pos < strlen($str)) $code .= substr($str, $start_pos);
        }
        return $code; 
    }
    
    private function ebp_parser($str)
    {
        $i = 1;
        $pairs = explode($this->ebp[0], $str);
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
                        $ret = $this->replace_strvars($pair, $this->ebp[0], $this->ebp[1], $ret['continue'] ? $ret : null);
                        $i++;
                    } else {
                        throw new \Exception('read error: '.$count);
                    }
                } while ($ret['continue'] === true);
                if ($fchar === '$') {
                    $tpl .= '<?php echo htmlspecialchars('.$this->read_unit($ret['code'], $ret['vars']).'); ?>';
                } else {
                    $tpl .= '<?php echo '.$this->read_unit($ret['code'], $ret['vars']).'; ?>';
                }
                if(strlen($pair) - $ret['pos'] > 1) $tpl .= substr($pair, $ret['pos']+1);
            }
        }
        return $tpl;
    }
    
    private function sbp_parser($str)
    {
        $reg = "/".preg_quote($this->sbp[0])."(".implode('|', $this->structure).").+/";
        $str = preg_replace_callback($reg, function ($match) {
            $tmp = '';
            $slen = strlen($this->sbp[0]);
            $elen = strlen($this->sbp[1]);
            $ret = $this->replace_strvars(substr($match[0], $slen), $this->sbp[0], $this->sbp[1]);
            if ($ret['continue']) throw new \Exception('read error: '.$match[0]);
            $val = $this->restore_strvars(substr($ret['code'], strlen($match[1])+1), $ret['vars']);
            $structure_ret = $this->read_structure($match[1], $val);
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
        $reg = "/".preg_quote($this->sbp[0])."\/(if|each|for|switch)".preg_quote($this->sbp[1])."/";
        $str = preg_replace($reg, '<?php } ?>', $str);
        if ($str) return $str;
        throw new \Exception('sbp_parser error: '.$str);
    }
    
    private function tag_parser($str)
    {
        $reg = "/<([a-z]+)[ \t]+".$this->tag_attr_prefix."(".implode('|', $this->structure).").+/";
        if (preg_match_all($reg, $str, $matchs, PREG_OFFSET_CAPTURE)) {
            $tpl = '';
            $start_pos = 0;
            $end_tags = array();
            $skip_num = array();
            foreach ($matchs[0] as $i => $match) {
                $tmp = substr($str, $start_pos, $match[1]-$start_pos);
                $blank = $this->read_left_blank($tmp);
                $tpl .= $end_tags ? $this->complete_end_tag($tmp, $end_tags, $skip_num) : $tmp;
                $ret = $this->replace_strvars($match[0], $start = '<', $end = '>');
                $tag = $this->read_tag($ret['code'].'>', $ret['vars']);
                $tpl .= implode("\r\n".$blank, $tag['code']);
                if ($matchs[1][$i][0] != $this->blank_tag) {
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
                    $tpl .= $end_tags ? $this->complete_end_tag($end_html, $end_tags, $skip_num, $blank) : $end_html;
                }
                $start_pos = strlen($match[0])+$match[1];
            }
            $tpl .= $end_tags ? $this->complete_end_tag(substr($str, $start_pos), $end_tags, $skip_num) : substr($str, $start_pos);
            return $tpl;
        }
        return $str;
    }
    
    private function complete_end_tag($str, &$end_tags, &$skip_num, $blank = null)
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
                            if ($end_tags[$i] !== $this->blank_tag) {
                                $code .= $match[0]."\r\n".($blank ? $blank : $this->read_left_blank($tmp));
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
    
    private function read_tag($tag, $vars)
    {
        $end = false;
        $html = '';
        $code = array();
        $has_noas_attr = false;
        $reg = "/".$this->tag_attr_prefix."(".implode('|', $this->structure).")(=\\$[1-9])?/";
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
                $attr_ret = $this->read_structure($attr[0], $val);
                $code[] = '<?php '.$attr_ret['code'].' ?>';
                if(!$end) $end = $attr_ret['end'];
                $start_pos = $matchs[0][$i][1]+strlen($matchs[0][$i][0]);
            }
            $html .= substr($tag, $start_pos);
        }
        if ($vars) $html = $this->restore_strvars($html, $vars);
        return array('html'=>$html, 'code'=>$code, 'end'=>$end);
    }
    
    private function read_structure($structure, $val = null)
    {
        $end = false;
        $code = '';
        if ($val) $ret = $this->replace_strvars($val);
        switch ($structure) {
            case 'as':
                $pairs = explode(';', $ret['code']);
                foreach ($pairs as $pair) {
                    $item = explode('=', trim($pair));
                    if (count($item) !== 2) throw new \Exception('read_structure error: '.$pair);
                    $kvar = $this->read_argument(trim($item[0]), $ret['vars']);
                    $vvar = $this->read_argument(trim($item[1]), $ret['vars']);
                    $code .= $kvar['value'].' = '.$vvar['value'].';';
                }
                break;
            case 'if':
                $end = true;
                $code = 'if ('.$this->read_exp($ret['code'], $ret['vars'], $this->operator).') {';
                break;
            case 'elseif':
                $end = true;
                $code = ' elseif ('.$this->read_exp($ret['code'], $ret['vars'], $this->operator).') {';
                break;
            case 'else':
                $end = true;
                $code = ' else {';
                break;
            case 'switch':
                $end = true;
                $argument = $this->read_argument($ret['code'], $ret['vars']);
                $code =  'switch ('.$argument['value'].') {';
                break;
            case 'case':
                $argument = $this->read_argument($ret['code'], $ret['vars']);
                $code = 'case: '.$argument['value'];
                break;
            case 'default':
                $code = 'default: ';
                break;
            case 'each':
                $end = true;
                $pairs = explode(' as ', $ret['code']);
                if (count($pairs) === 2) {
                    $argument = $this->read_argument($pairs[0], $ret['vars']);
                    if ($argument['type'] === 'mixed') {
                        $list = explode(' ', trim($pairs[1]), 2);
                        if (count($list) === 1) {
                            if ($this->is_varname_chars($list[0])) {
                                $code = 'foreach( '.$argument['value'].' as $'.$list[0].') {';
                            }
                        } else {
                            if ($this->is_varname_chars($list[0]) && $this->is_varname_chars($list[1])) {
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
                    $for_operator = $this->operator;
                    $for_operator[] = ';';
                    $code = 'for ('.$this->read_exp($ret['code'], $ret['vars'], $for_operator).') {';
                } else {
                    throw new \Exception('read_structure error: '.$ret['code']);
                }
                break;
            case 'include':
                $argument = $this->read_argument($ret['code'], $ret['vars']);
                $code = 'include $this->_include('.$argument['value'].', __DIR__);';
                break;
            case 'extend':
                $argument = $this->read_argument($ret['code'], $ret['vars']);
                print_r($ret);
                $code = '$this->_extend('.$argument['value'].', __FILE__);';
                break;    
            default:
                throw new \Exception('read_structure error: '.$structure);
        }
        return array('code'=>$code, 'end'=>$end);
    }
    
    private function read_unit($str, $vars)
    {
        $i = 0;
        $code = '';
        $prev = null;
        $len = strlen($str);
        while ($i < $len) {
            $unit = $this->read_word(substr($str, $i));
            $i += $unit['seek'];
            switch ($unit['end']) {
                case '?':
                    $arr = explode(':', substr($str, $i));
                    if (empty($code)) {
                        $code = $this->replace_var($unit['code']);
                    } else {
                        $code .= '[\''.$unit['code'].'\']';
                    }
                    return $code.' ? '.$this->read_unit($arr[0], $vars). ':' .$this->read_unit($arr[1], $vars);
                case '[':
                    if (empty($unit['code'])) {
                        if ($prev === '.' || empty($code)) throw new \Exception('read error');
                    }
                    $pos = $this->find_end_pos(substr($str, $i), '[', ']');
                    $argument = $this->read_argument(substr($str, $i, $pos), $vars);
                    $i += $pos+1;
                    if($argument['type'] === 'mixed' || $argument['type'] === 'number' || $argument['type'] === 'string') {
                        if ($code) {
                            if ($unit['code']) $code .= '[\''.$unit['code'].'\']';
                            $code .= '['.$argument['value'].']';
                        } else {
                            $code = $this->replace_var($unit['code']).'['.$argument['value'].']';
                        }
                    } else {
                        throw new \Exception('read_unit error :'.$str);
                    }
                    $prev = '[';
                    break;
                case '(':
                    if (empty($unit['code'])) {
                        if (empty($code) && $str{$len-1} === ')') {
                            $argument = $this->read_argument(substr($str, $i, -1), $vars);
                            if ($argument['type'] === 'string') {
                                return $argument['value'];
                            }
                        }
                        throw new \Exception('read_unit error: '.$str);
                    }
                    $arguments = $code ? array($code) : array();
                    $pos = $this->find_end_pos(substr($str, $i), '(', ')');
                    $args_str = trim(substr($str, $i, $pos));
                    if (!empty($args_str)) {
                        $args = explode(',', substr($str, $i, $pos));
                        foreach ($args as $arg) {
                            $arguments[] = $this->read_argument($arg, $vars)['value'];
                        }
                    }
                    $i += $pos+1;
                    $code = $this->replace_function($unit['code'], $arguments);
                    $prev = '(';
                    break;
                case '.':
                    if (($prev === '.' || empty($code)) && empty($unit['code'])) {
                        throw new \Exception('read_unit error: '.$str);
                    }
                    if (empty($code)) {
                        $code = $this->replace_var($unit['code']);
                    } elseif (!empty($unit['code'])){
                        $code .= '[\''.$unit['code'].'\']';
                    }
                    $prev = '.';
                    break;
                case '':
                    if (empty($code)) {
                        return $this->replace_var($unit['code']);
                    } else {
                        return $code.'[\''.$unit['code'].'\']';
                    }
            }
        }
        return $code;
    }
    
    public function read_array($str, $vars)
    {

    }
    
    public function read_json($str)
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
            if ($this->is_quote_char($char)) {
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
    
    private function read_exp($str, $vars, $exp)
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
                    $ret = $this->read_blank($tmp);
                    $code .= $ret['left'].$this->read_argument($ret['str'], $vars)['value'].$ret['right'];
                }
                $code .= $match[0];
                $start_pos = strlen($match[0])+$match[1];
            }
            if ($start_pos < strlen($str)) {
                $ret = $this->read_blank(substr($str, $start_pos));
                $code .= $ret['left'].$this->read_argument($ret['str'], $vars)['value'].$ret['right'];
            }
            return $code;
        }
        return $this->read_argument($str, $vars)['value'];
    }
    
    private function read_word($str)
    {
        $code = '';
        $is_end = false;
        $len = strlen($str);
        for($i=0; $i<$len; $i++) {
            $c = $str{$i};
            if ($this->is_varname_char($c)) {
                if ($is_end || (!$code && is_numeric($c))) throw new \Exception('read_word error: '.$str);
                $code .= $c;
            } else {
                if($c === '.' || $c === '[' || $c === '(') {
                    return array('code'=> $code, 'seek'=>$i+1, 'end'=>$c);
                } elseif ($this->is_blank_char($c)) {
                    if($code) $is_end = true;
                } else {
                    throw new \Exception('read_word error: '.$str);
                }
            }
        }
        return array('code'=> $code, 'seek'=>$len, 'end'=>'');
    }
    
    private function read_argument($str, $vars)
    {
        $str = trim($str);
        if ($str === 'true' || $str === 'false' || $str === 'null') {
            return array('type'=>$str, 'value'=>$str);
        } elseif (preg_match("/^\\$([1-9])$/", $str, $match)) {
            return array('type'=>'string', 'value'=>$vars[$match[1]-1]);
        } elseif (is_numeric($str)) {
            return array('type'=>'number', 'value'=>$str);
        } elseif ($str{0} === '[' || $str{0} === '{') {
            return array('type'=>'array', 'value'=>$this->read_array($str, $vars));
        } else {
            return array('type'=>'mixed', 'value'=>$this->read_unit($str, $vars));
        }
    }
    
    private function read_blank($str)
    {
        $lpos = $rpos = 0;
        $left = $right = $tmp = '';
        $len = strlen($str);
        for ($i=0; $i<$len; $i++) {
            if ($this->is_blank_char($str{$i})) {
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
                if ($this->is_blank_char($str{$i})) {
                    $right .= $str{$i};
                } else {
                    $rpos = $i;
                    break;
                }
            }
            return array('left'=>$left, 'right'=>$right, 'str'=>substr($str, $lpos, $rpos-$lpos+1));
        }
    }
    
    private function read_left_blank($str)
    {
        $blank = '';
        $i = strlen($str)-1;
        while ($i >= 0) {
            if (isset($str{$i})) {
               if ($this->is_blank_char($str{$i})) {
                   $blank .= $str{$i};
               } else {
                   return $blank;
               }
            }
            $i--;
        }
    }
    
    private function replace_var($var)
    {
        if (isset($this->var_alias[$var])) {
            return '$'.$this->var_alias[$var];
        } else {
            return '$'.$var;
        }
    }
    
    private function replace_function($name, $args)
    {
        if (isset($this->alias_function[$name])) {
            if (count($args) > 0) {
                $replace_pairs = array();
                foreach ($args as $i => $arg) {
                    $replace_pairs['$'.$i] = $arg;
                }
                return strtr($this->alias_function[$name], $replace_pairs);
            } else {
                return $this->alias_function[$name];
            }
        } else{
            return 'call_user_func($this->functions["'.$name.'"],'.implode(',', $args).')';
        }
    }
    
    private function replace_strvars($str , $start = null, $end = null, $continue = null)
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
            if ($this->is_quote_char($char)) {
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
            } elseif ($this->is_newline_char($char)) {
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
    
    private function restore_strvars($code, $vars)
    {
        $replace_pairs = array();
        foreach ($vars as $i => $v) {
            $replace_pairs['$'.($i+1)] = $v;
        }
        return strtr($code, $replace_pairs);
    }
    
    private function find_end_pos($str, $start, $end)
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
     
    private function is_quote_char($char)
    {
        return $char === '"' || $char === "'";
    }    
    
    private function is_blank_char($char)
    {
        return $char === ' ' || $char === "\t";
    }
    
    private function is_newline_char($char)
    {
        return $char === "\r" || $char === "\n";
    }
    
    private function is_varname_char($char)
    {
        $ascii = ord($char);
        return ($ascii === 95 || ($ascii > 47 && $ascii < 58) || ($ascii > 64 && $ascii < 91) || ($ascii > 96 && $ascii < 123));
    }
    
    private function is_varname_chars($str)
    {
        $len = strlen($str);
        if($len > 0 && $this->is_varname_char($str{0}) && !is_numeric($str{0})) {
            for ($i=1; $i<$len; $i++) {
                if (!$this->is_varname_char($str{$i})) return false;
            }
            return true;
        }
        return false;
    }
}
