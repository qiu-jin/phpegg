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
        // 插入标签
        'inner_tag'             => 'inner',
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
        'text_escape_sign'      => [':', '!'],
        // 内置变量标识符
        'template_var_sign'     => '$',
        // 原样输出文本标识符（不解析文本插入边界符以其内内容）
        'verbatim_text_sign'    => '!',
        // 是否支持原生PHP函数
        'enable_native_function'    => false,
        // 
        'view_filter_code'          => View::class.'::callFilter(%s)',
        // include 
        'view_include_code'         => 'include '.View::class.'::path(%s);',
        // check expired
        'view_check_expired_code'   => 'if ('.View::class.'::checkExpired(__FILE__, %s)) return include __FILE__;',
        // read template
        'view_read_template_method' => View::class.'::readTemplate',
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
        'default'       => '($0 ?? $1)',
        // 转为字符串
        'str'           => 'strval($0)',
        // 转为数字
        'num'           => '($0+0)',
        // 字符串拼接
        'concat'        => '($0.$1)',
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
        $ck  = [];
        // extends
        $str = self::readExtends($str, $ck);
        // inner
        $str = self::readInner($str, $ck);
        // 检查模版更新
        $res = '';
        if ($ck) {
            $arg  = '"'.implode('", "', array_unique($ck)).'"';
            $res .= self::wrapCode(sprintf(self::$config['view_check_expired_code'], $arg)).PHP_EOL;
        }
        $end = [];
        if ($ret = self::parseEndTag($str, 'verbatim')) {
            $pos = 0;
            foreach ($ret as $v) {
                $res .= self::readTagText(substr($str, $pos, $v['pos'][0] - $pos), $end);
                $res .= $v['text'];
                $pos  = $v['pos'][1];
            }
            $str = substr($str, $pos);
        }
        if (!$end) {
            return $res.self::readTagText($str, $end);
        }
        throw new TemplateException('complie error: 结构语句标签未闭合');
    }
    
    protected static function readTagText($str, &$end) 
    {
        return self::readText(self::readStructTag(self::readInclude($str), $end));
    }
    
    /*
     * 读取解析inner标签
     */
    protected static function readInner($str, &$ck)
    {
        return preg_replace_callback(self::tagRegex('inner'), function ($matches) use (&$tpls) {
            $attrs = self::parseSelfEndTagAttrs($matches, ['name']);
            $ck[]  = $attrs['name'];
            return self::$config['view_read_template_method']($attrs['name']);
        }, $str);
    }
    
    /*
     * 读取解析include标签
     */
    protected static function readInclude($str)
    {
        return preg_replace_callback(self::tagRegex('include'), function ($matches) {
            $vname = self::$config['arg_attr_prefix'].'name';
            $attrs = self::parseSelfEndTagAttrs($matches, ['name', $vname]);
            if (isset($attrs['name'])) {
                $arg = '"'.$attrs['name'].'"';
            } else {
                $arg = self::parseValue($attrs[$name]);
            }
            return self::wrapCode(sprintf(self::$config['view_include_code'], $arg));
        }, $str);
    }
    
    /*
     * 读取解析插值语句
     */
    protected static function readText($str)
    {
        $l = preg_quote(self::$config['text_border_sign'][0]);
        $r = preg_quote(self::$config['text_border_sign'][1]);
        return preg_replace_callback("/$l(.*?)$r/", function ($matches) use ($str) {
            // 不解析，原样输出
            if ($matches[0][0] === self::$config['verbatim_text_sign']) {
                return substr($matches[0], 1);
            }
            // 是否转义
            $val = $matches[1];
            $escape = self::$config['auto_escape_text'];
            if (in_array($c = $val[0], self::$config['text_escape_sign'])) {
                $val = substr($val, 1);
                $escape  = !array_search($c, self::$config['text_escape_sign']);
            }
            $code = self::parseValue($val);
            return self::wrapCode($escape ? "echo htmlentities($code);" : "echo $code;");
        }, $str);
    }
    
    /*
     * 读取解析extends标签
     */
    protected static function readExtends($str, &$ck)
    {
        if (!preg_match_all(self::tagRegex('extends'), $str, $matches, PREG_OFFSET_CAPTURE)) {
            return $str;
        }
        if (count($matches[0]) > 1) {
            throw new TemplateException('readExtends error: 不允许有多条extends标签语句');
        }
        if (count($matches[0][0][1]) !== 0) {
            throw new TemplateException('readExtends error: extends标签语句必须在模版开头，其前不允许有非空白字符');
        }
        $name = self::parseSelfEndTagAttrs($matches[0][0][0], ['name'])['name'];
        $ck[] = $name;
        // 读取子模版block
        if ($ret = self::parseEndTag(substr($str, strlen($matches[0][0][0])), 'block', ['name'])) {
            foreach ($sret as $v) {
                $blocks[$v['attrs']['name']] = $v['text'];
            }
        }
        // 替换父模版block
        $parent = preg_replace_callback(self::funcRegex('block'), function ($matchs) use ($blocks) {
            return $blocks[$matchs[1]] ?? '';
        }, self::$config['view_read_template_method']($name));
        if ($ret = self::parseEndTag($parent, 'block', ['name'])) {
            $pos = 0;
            $res = '';
            $regex = self::funcRegex('parent', false);
            foreach ($ret as $v) {
                $res .= substr($parent, $pos, $v['pos'][0] - $pos);
                $pos  = $v['pos'][1];
                if (isset($blocks[$v['attrs']['name']])) {
                    $res .= preg_replace_callback($regex, function ($matchs) use ($v) {
                        return $v['text'];
                    }, $blocks[$v['attrs']['name']]);
                } else {
                    $res .= $v['text'];
                }
            }
            return $res.substr($parent, $pos);
        }
        return $parent;
    }
    
    /*
     * 读取解析控制结构语句标签
     */
    protected static function readStructTag($str, &$end)
    {
        $r = '(?:"[^"]*"|\'[^\']*\')';
        $prefix = preg_quote(self::$config['assign_attr_prefix'].self::$config['struct_attr_prefix']);
        $regex  = "/<(\w+)\s+(?:\s*\w+\s*=\s*$r)*(?:\s*[$prefix]\w+(?:\s*=\s*$r)?)+(?:\s*\w+\s*=\s*$r)*\s*\/?>/";
        if (!preg_match_all($regex, $str, $matches, PREG_OFFSET_CAPTURE)) {
            return $str;
        }
        $pos = 0;
        $res = '';
        foreach ($matches[0] as $i => $match) {
            if ($pos > $match[1]) {
                throw new TemplateException("readTag error: 文本读取偏移地址错误");
            }
            // 读取左侧HTML
            $left  = substr($str, $pos, $match[1] - $pos);
            // 读取空白内容
            $blank = self::readLeftBlank($left);
            // 拼接左侧内容，如有为闭合语句，尝试闭合处理。
            $res  .= $end ? self::completeStructTag($left, $end) : $left;
            // 读取解析标签内代码
            $attrs = self::readTagStructAttr($match[0]);
            // 处理if与elseif else的衔接
            if ($attrs['is_else']) {
                $l = 2 + strlen(PHP_EOL);
                if (substr($res, -$l, 2) == '?>') {
                    $res = substr($res, 0, -$l).substr(implode(PHP_EOL.$blank, $attrs['code']), 6);
                } else {
                    throw new TemplateException('readTag error: 衔接if else失败');
                }
            } else {
                $res .= implode(PHP_EOL.$blank, $attrs['code']);
            }
            // 补全空白内容
            $res .= PHP_EOL.$blank;
            // 如果是空白标签则忽略标签HTML代码
            if ($matches[1][$i][0] != self::$config['blank_tag']) {
                $res .= $attrs['html'];
            }
            // 如果是自闭合标签则自行添加PHP闭合，否则增加未闭合标签语句数据供下步处理。
            if (substr($attrs['html'], -2) === '/>') {
                $res .= str_repeat(PHP_EOL.$blank.self::wrapCode('}'), $attrs['count']);
            } else {
                $end[] = [
                    'num'   => 0, // HTML闭合标签层数
                    'tag'   => $matches[1][$i][0], // HTML标签名
                    'count' => $attrs['count'], // 补全的PHP闭合标签数
                ];
            }
            // 重设文本处理位置
            $pos = strlen($match[0]) + $match[1];
        }
        // 处理最后部分
        if ($tmp = substr($str, $pos)) {
            $res.= $end ? self::completeStructTag($tmp, $end) : $tmp;
        }
        return $res;
    }
    
    /*
     * 合并完成模版标签闭合
     */
    protected static function completeStructTag($str, &$end, $blank = null)
    {
        $res = '';
        do {
            $i = count($end) - 1;
            $tag = $end[$i]['tag'];
            if (!preg_match_all("/(<$tag|<\/$tag>)/", $str, $matches, PREG_OFFSET_CAPTURE)) {
                // 无匹配则直接拼接剩余部分返回
                return $res.$str;
            }
            $pos = 0;
            foreach ($matches[0] as $match) {
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
            $str = substr($str, $pos);
        } while ($i > 0);
        return $res.$str;
    }
    
    /*
     * 读取解析流程控制语句
     */
    protected static function readTagStructAttr($str)
    {
        // 标签HTML代码
        $html    = '';
        // 标签PHP代码
        $code    = [];
        // 标签内结构语句条数
        $count   = 0;
        $is_else = false;
        $prefix  = preg_quote(self::$config['assign_attr_prefix'].self::$config['struct_attr_prefix']);
        $regex   = "/\s*([$prefix])(\w+)(?:\s*=\s*(\"[^\"]*\"|'[^']*'))?/";
        if (preg_match_all($regex, $str, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = 0;
            foreach ($matches[1] as $i => $match) {
                $html .= trim(substr($str, $pos, $matches[0][$i][1] - $pos));
                $pref  = $matches[1][$i][0];
                $name  = $matches[2][$i][0];
                if ($val = $matches[3][$i][0]) {
                    $val = substr($val, 1, -1);
                } else {
                    if ($name != 'else' && $pref == self::$config['struct_attr_prefix']) {
                        throw new TemplateException("readTagAttr error: 标签属性值不能为空");
                    }
                }
                // 是否为赋值语句
                if ($pref == self::$config['assign_attr_prefix']) {
                    $code[] = self::wrapCode("$$name = ".self::parseValue($val).';');
                } else {
                    $count++;
                    if ($name == 'else' || $name == 'elseif') {
                        $is_else = true;
                        if ($code) {
                            throw new TemplateException("readTagAttr error: 单个标签内else或elseif前不允许有其它语句");
                        }
                    }
                    $code[] = self::wrapCode(self::readControlStruct($name, $val));
                }
                $pos = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            }
            $html .= substr($str, $pos);
        }
        return compact('html', 'code', 'count', 'is_else');
    }
    
    /*
     * 读取语句结构
     */
    protected static function readControlStruct($name, $val)
    {
        switch ($name) {
            case 'if':
                return 'if ('.self::parseValue($val).') {';
            case 'elseif':
                return 'elseif ('.self::parseValue($val).') {';
            case 'else':
                return 'else {';
            case 'each':
                $arr = explode(' in ', $val, 2);
                if (isset($arr[1])) {
                    $kv  = explode(':', trim($arr[0]), 2);
                    $val = $arr[1];
                    if (count($kv) == 1) {
                        $as = '$'.$kv[0];
                    } else {
                        if (empty($kv[0])) {
                            if (self::isVarChars($kv[1])) {
                                $as = '$'.$kv[1];
                            }
                        } elseif (empty($kv[1])) {
                            if (self::isVarChars($kv[0])) {
                                $as = '$'.$kv[0].' => $__';
                            }
                        } else {
                            if (self::isVarChars($kv[0]) && self::isVarChars($kv[1])) {
                                $as = '$'.$kv[0].' => $'.$kv[1];
                            }
                        }
                    }
                    if (!isset($as)) {
                        break;
                    }
                } else {
                    $as = '$key => $val';
                }
                return 'foreach('.self::parseValue($val)." as $as) {";
            case 'for':
                if (count($arr = explode(';', $attr['code'], 2)) == 2) {
                    foreach($arr as $v) {
                        $ret[] = self::parseValue($v, $attr['vars']);
                    }
                    return 'for ('.implode(';', $ret).') {';
                }
                break;
        }
        throw new TemplateException("readControlStruct error: 非法语句 $name ($val)");
    }
    
    /*
     * 读取语句单元
     */
    protected static function parseValue($val, $strs = null)
    {
        if ($strs === null) {
            extract(self::extractString($val));
        }
        if (preg_match('/\w\s+\w/', $val)) {
            throw new TemplateException("parseValue error: 非法空格 $val");
        }
        $val = preg_replace('/\s+/', '', $val);
        $ret = null;
        $tmp = null;
        $len = strlen($val);
        for($i = 0; $i < $len; $i++) {
            $c = $val[$i];
            if (self::isVarChar($c)) {
                $tmp .= $c;
                continue;
            }
            switch ($c) {
                // 数组或函数
                case '.':
                    $ret = self::readMacroValue($ret, $tmp, $val, $len, $i, $strs);
                    break;
                // 括号
                case '(':
                    if (!isset($ret) && !isset($tmp) && ($pos = self::findEndPos($val, $len, $i, '(', ')'))) {
                        $ret = "(".self::parseValue(substr($val, $i + 1, $pos - $i - 1), $strs).")";
                        $i = $pos;
                        break;
                    }
                    throw new TemplateException("parseValue error: 非法$c语法");
                // 数组
                case '[':
                    if (!isset($tmp) && ($pos = self::findEndPos($val, $len, $i, '[', ']'))) {
                        if (isset($ret)) {
                            $key = self::parseValue(substr($val, $i + 1, $pos - $i - 1), $strs);
                            $ret = $ret."[$key]";
                        } else {
                            $ret = self::readArrayValue(substr($val, $i, $pos), $strs);
                        }
                        $i = $pos;
                        break;
                    }
                    throw new TemplateException("parseValue error: 非法$c语法");
                // 数组
                case '{':
                    if (!isset($ret) && !isset($tmp) && ($pos = self::findEndPos($val, $len, $i, '{', '}'))) {
                        $ret = self::readArray(substr($val, $i, $pos), $strs);
                        $i = $pos;
                        break;
                    }
                    throw new TemplateException("parseValue error: 非法$c语法");
                // 原生函数
                case '$':
                    if (!isset($ret) && !isset($tmp)) {
                        if (preg_match("/^\d+/", substr($val, $i + 1), $matchs)) {
                            $ret .= self::injectString($matchs[0], $strs);
                            $i += strlen($matchs[0]);
                        } else {
                            $ret = self::readFunctionValue($ret, $tmp, $val, $len, $i, $strs);
                        }
                        break;
                    }
                    throw new TemplateException("parseValue error: 非法$c语法");
                // 三元表达式
                case '?':
                    $ret = self::readThreeMetaValue($ret, $tmp, $val, $len, $i, $strs);
                    break;
                default:
                    if (in_array($c, ['+', '-', '*', '/', '%'])
                        || in_array($c, ['!', '&', '|', '=', '>', '<'])
                    ) {
                        // 对象操作符
                        if ($c == '-' && substr($val, $i + 1, 1) === '>') {
                            $ret = self::readObjectValue($ret, $tmp, $val, $len, $i, $strs);
                        } else {
                            $ret = self::readInitValue($ret, $tmp);
                            return "$ret $c ".self::parseValue(substr($val, $i + 1), $strs);
                        }
                        break;
                    }
                    throw new TemplateException("parseValue error: 非法字符$c");
            }
            $tmp = null;
        }
        return self::readInitValue($ret, $tmp);
    }
    
    /*
     * 变量
     */
    protected static function readVarValue($var)
    {
        if (is_numeric($var) || in_array($var, ['true', 'false', 'null'], true)) {
            return $var;
        }
        return self::$vars[$var] ?? '$'.$var;
    }
    
    /*
     * 
     */
    protected static function readInitValue($ret, $tmp)
    {
        if (isset($ret) && isset($tmp)) {
            throw new TemplateException("readInitValue error: $tmp");
        }
        return isset($tmp) ? self::readVarValue($tmp) : $ret;
    }
    
    /*
     * 
     */
    protected static function readArrayValue($val, $strs)
    {
        return "jsondecode('".self::injectString($val, $strs)."')";
    }
    
    /*
     * 
     */
    protected static function readMacroValue($ret, $tmp, $val, $len, &$pos, $strs)
    {
        if (!isset($ret) && !isset($tmp)) {
            throw new TemplateException("readMacroValue error: 空属性");
        }
        $res = self::readItemVal($val, $pos + 1, $len)
        $ret = self::readInitValue($ret, $tmp);
        $pos = $res['pos'];
        if (empty($res['fun'])) {
            return $ret."['".$res['val']."']";
        }
        $args = self::readArguments($val, $len, $pos, $strs, $ret ? [$ret] : []);        
        if (isset(self::$functions[$res['val']])) {
            return self::injectString(self::$functions[$res['val']], $args);
        }
        $args = $args ? ', '.implode(', ', $args) : '';
        return sprintf(self::$config['view_filter_code'], $res['val'].$args);
    }
    
    /*
     * 
     */
    protected static function readObjectValue($ret, $tmp, $val, $len, &$pos, $strs)
    {
        if (!isset($ret)) {
            throw new TemplateException("readObjectValue error: 对象为空");
        }
        $res = self::readItemVal($val, $pos + 2, $len)
        $ret = self::readInitValue($ret, $tmp);
        $pos = $res['pos'];
        if (empty($res['fun'])) {
            return "$ret->".$res['val'];
        }
        $args = implode(', ', self::readArguments($val, $len, $pos, $strs));
        return "$ret->$res[val]($args)";
    }
    
    /*
     * 
     */
    protected static function readFunctionValue($ret, $tmp, $val, $len, &$pos, $strs)
    {
        if (!self::$config['enable_native_function']) {
            throw new TemplateException("readFunctionValue error: 未开启原生PHP函数支持");
        }
        if (!$lpos = strpos($val, '(', $pos)) {
            throw new TemplateException("readFunctionValue error: 函数缺少调用符()");
        }
        $arr = explode('.', substr($val, $pos + 1, $lpos - $pos - 1));
        foreach ($arr as $v) {
            if (!self::isVarChars($v)) {
                throw new TemplateException("readFunctionValue error: 非法字符 $v");
            }
        }
        $pos  = $lpos;
        $args = implode(', ', self::readArguments($val, $len, $pos, $strs));
        if (count($arr) == 1) {
            return "$arr[0]($args)";
        }
        $m = array_pop($arr);
        return implode('\\', $arr)."::$m($args)";
    }
    
    /*
     * 
     */
    protected static function readThreeMetaValue($ret, $tmp, $val, $len, &$pos, $strs)
    {
        if (isset($tmp)) {
            $ret = self::readVarValue($ret, $tmp);
        }
        if ($val[$i] == ':' || $val[$i] == '?') {
            return "$ret ?".$val[$i].' '.self::parseValue(substr($val, $i + 2), $strs);
        } else {
            $pos = 0;
            while ($pos = strpos($val, ':', $pos)) {
                if (substr($val, $pos + 1, 1) != ':') {
                    $left  = substr($val, 0, $pos);
                    $right = substr($val, $pos + 1);
                    break;
                }
            }
            if (isset($left)) {
                return $ret.' ? '.self::parseValue($left, $strs). ' : ' .self::parseValue($right, $strs);
            }
        }
    }
    
    /*
     * 
     */
    protected static function readItemVal($val, $pos, $len)
    {
        $ret = null;
        while ($pos < $len) {
            $c = $val[$pos];
            if (!self::isVarChar($c)) {
                break;
            }
            $ret .= $c;
            $pos++;
        }
        if (self::isVarChars($ret)) {
            if (substr($val, $pos, 1) !== '(') {
                return ['val' => $ret, 'pos' => $pos - 1];
            }
            return ['val' => $ret, 'pos' => $pos, 'fun' => true];
        }
        throw new TemplateException("readItemVal error: 非法字符 $ret");
    }
    
    /*
     * 
     */
    protected static function readArguments($val, $len, &$pos, $strs, $args = [])
    {
        $epos = self::findEndPos($val, $len, $pos, '(', ')');
        if ($tmp = trim(substr($val, $pos + 1, $epos - $pos - 1))) {
            foreach (explode(',', $tmp) as $v) {
                $args[] = self::parseValue(trim($v), $strs);
            }
        }
        $pos = $epos;
        return $args;
    }
    
    /*
     * 
     */
    protected static function injectString($val, $strs)
    {
        return preg_replace_callback('/\\$(\d+)/', function ($matches) use ($strs) {
            return $strs[$matches[1]] ?? '';
        }, $val);
    }
    
    /*
     * 提取字符串
     */
    protected static function extractString($val)
    {
        $tmp   = '';
        $ret   = '';
        $strs  = [];
        $quote = null;
        $len   = strlen($val);
        for($i = 0; $i < $len; $i++) {
            $c = $val[$i];
            if (self::isQuoteChar($c)) {
                if ($quote) {
                    if ($quote === $c) {
                        if ($val[$i - 1] === '\\') {
                            $tmp .= $c;
                            continue;
                        }
                        $ret  .= '$'.count($strs);
                        $strs[]= $quote.$tmp.$quote;
                        $tmp   = '';
                        $quote = null;
                    } else {
                        $tmp  .= $c;
                    }
                } else {
                    $quote = $c;
                }
            } else {
                if ($quote) {
                    $tmp .= $c;
                } else {
                    $ret .= $c;
                }
            }
        }
        if (!$quote) {
            return ['val' => $ret, 'strs' => $strs];
        }
        throw new TemplateException("extractString error: 字符串引号未闭合");
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
        for ($i = $len - 1; $i >= 0; $i--) {
            if (self::isBlankChar($str[$i])) {
                $right .= $str[$i];
            } else {
                $rpos = $i;
                break;
            }
        }
        return ['left' => $left, 'right' => $right, 'str'=> substr($str, $lpos, $rpos - $lpos + 1)];
    }
    
    /*
     * 读取左边空白符
     */
    protected static function readLeftBlank($str)
    {
        $ret = '';
        for ($i = strlen($str) - 1; $i >= 0; $i--) {
            if (self::isBlankChar($str[$i])) {
                $ret .= $str[$i];
            } else {
                return $ret;
            }
        }
    }
    
    /*
     * 标签正则
     */  
    protected static function tagRegex($tag)
    {        
        return '/<'.self::$config[$tag.'_tag'].'(?:"[^"]*"|\'[^\']*\'|[^\'">])*>/';
    }
    
    /*
     * 函数正则
     */  
    protected static function funcRegex($func, $arg = true)
    {
        $s = $arg ? '"(\w+)"' : '';
        return '/'.implode("@\s*$func\($s\)\s*", self::$config['text_border_sign']).'/';
    }
    
    /*
     * 解析标签属性
     */  
    protected static function parseSelfEndTagAttrs($str, $attrs = null)
    {
        if (substr($str, -2) != '/>') {
            throw new TemplateException("parseTagAttrs error: 必须自闭合标签 $str");
        }
        $prefix = preg_quote(self::$config['arg_attr_prefix']);
        if (preg_match_all('/([\w|-'.$prefix.']+)\s*=\s*(?:"([^"]+)"|\'([^\']+)\')/', $str, $matches)) {
            foreach ($matches[1] as $i => $attr) {
                $ret[$attr] = $matches[2][$i] ?: $matches[3][$i];
            }
            if ($attrs) {
                foreach ($attrs as $a) {
                    if (isset($ret[$a])) {
                        $_ret[$a] = $ret[$a];
                    } else {
                        throw new TemplateException("parseTagAttrs error: 标签值$a为空 $str");
                    }
                }
                return $_ret;
            }
            return $ret;
        }
        throw new TemplateException("parseTagAttrs error: 标签值为空 $str");
    }
    
    /*
     * 
     */
    protected static function parseEndTag($str, $tag, $attrs = null)
    {        
        if (!preg_match_all(self::tagRegex($tag), $str, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }
        $pos = 0;
        $end_tag = "</$tag>";
        foreach ($matches[0] as $i => $match) {
            if ($match[1] >= $pos) {
                if (!$pos = stripos($str, $end_tag, $match[1])) {
                    throw new TemplateException('readTagWithEnd error: $tag标签未闭合');
                }
                $start = $match[1] + strlen($match[0]);
                $ret[] = [
                    'pos'   => [$match[1], $pos + strlen($verbatim_end)],
                    'text'  => substr($str, $start, $pos - $start),
                    'attrs' => self::parseTagAttrs($match[0], $attrs, false)
                ];
            }
            throw new TemplateException('readTagWithEnd error: $tag标签不允许嵌套');
        }
    }
    
    /*
     * 寻找语句结束符位置
     */ 
    protected static function findEndPos($val, $len, $pos, $left, $right)
    {
        $num = 0;
        for($i = $pos + 1; $i < $len; $i++) {
            $c = $val[$i];
            if ($c === $left) {
                $num++;
            } elseif ($c === $right) {
                if ($num === 0) {
                    return $i;
                }
                $num--;
            }
        }
        throw new TemplateException("findEndPos errer: $val 没有找到结束符 $right");
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
    protected static function isVarChar($char)
    {
        return preg_match('/\w$/', $char);
    }
    
    /*
     * 是否变量名字符串
     */ 
    protected static function isVarChars($str)
    {
        if($str && !in_array($str, ['true', 'false', 'null']) && self::isVarChar($str[0]) && !is_numeric($str[0])) {
            $len = strlen($str);
            for ($i = 1; $i < $len; $i++) {
                if (!self::isVarChar($str[$i])) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}
Template::init();
