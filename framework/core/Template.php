<?php
namespace framework\core;

use framework\util\Arr;
use framework\core\http\Request;
use framework\core\exception\TemplateException;

class Template
{
    protected static $init;
    // 配置
    protected static $config    = [
        // 空标签
        'blank_tag'             => 'php',
        // 块标签
        'block_tag'             => 'block',
        // 组件标签
        'component_tag'         => 'component',
        // 原生标签
        'verbatim_tag'          => 'verbatim',
        // 插入标签
        'insert_tag'            => 'insert',
        // 引用标签
        'include_tag'           => 'include',
        // 继承标签
        'extends_tag'           => 'extends',
        // 结构语句前缀符
        'struct_attr_prefix'    => '@',
        // 赋值语句前缀符
        'assign_attr_prefix'    => '$',
        // 参数语句前缀符
        'argument_attr_prefix'  => ':',
        // 文本插入左右边界符号
        'text_border_sign'      => ['{{', '}}'],
        // 文本插入是否自动转义
        'auto_escape_text'      => true,
        // 文本转义符号与反转义符号
        'text_escape_sign'      => [':', '!'],
        // 注释符号
        'note_text_sign'        => '#',
        // 原样输出标识符（不解析文本插入边界符以其内内容）
        'verbatim_text_sign'    => '!',
        // 是否允许在模版内使用PHP原生语法
        'allow_php_syntax'      => false,
        // 是否支持PHP函数
        'allow_php_functions'   => false,
        // 静态类公共名称空间
        'view_class_namespace'  => null,
        // 模版读取器
        'view_template_reader'  => View::class.'::readTemplate',
        // filter宏
        'view_filter_macro'     => View::class.'::filterMacro',
        // include宏
        'view_include_macro'    => View::class.'::includeMacro',
        // container宏
        'view_container_macro'  => View::class.'::ContainerMacro',
        // check expired宏
        'view_check_expired_macro'  => View::class.'::checkExpiredMacro',
    ];
    
    // 内置变量
    protected static $vars    = [
        
    ];
    
    // 内置函数
    protected static $filters = [
        // 是否不为空
        'has'           => '!empty($0)',
        // 是否为空
        'empty'         => 'empty($0)',
        // 是否存在
        'isset'         => 'isset($0)',
        // 默认值
        'default'       => '($0 ?? $1)',
        // 转为字符串
        'str'           => 'strval($0)',
        // 字符串拼接
        'concat'        => '($0.$1)',
        // 字符串拼接
        'format'        => 'sprintf($0, $1, ...)',
        // 字符串补全填充
        'pad'           => 'str_pad($0, $1, ...)',
        // 字符串替换
        'replace'       => 'str_replace($1, $2, $0)',
        // 字符串中字符位置
        'index'         => 'strpos($1, $0)',
        // 字符串截取
        'substr'        => 'substr($0, $1, $2)',
        // 字符串截取
        'slice'         => 'substr($0, $1 > 0 ? $1 - $0 : $1)',
        // 字符串重复
        'repeat'        => 'str_repeat($0, $1)',
        // 字符串长度
        'length'        => 'strlen($0)',
        // 字符串大写
        'lower'         => 'strtolower($0)',
        // 字符串小写
        'upper'         => 'strtoupper($0)',
        // 字符串首字母大写
        'ucfirst'       => 'ucfirst($0)',
        // 每个单词的首字母大写
        'capitalize'    => 'ucwords($0)',
        // 字符串剔除两端空白
        'trim'          => 'trim($0, ...)',
        // 文本换行符转换成HTML换行符
        'nl2br'         => 'nl2br($0)',
        // 字符串md5值
        'md5'           => 'md5($0)',
        // 字符串hash值
        'hash'          => 'hash($1, $0)',
        // 正则匹配
        'match'         => 'preg_match($1, $0, ...)',
        // 字符串HTML转义
        'escape'        => 'htmlentities($0)',
        // 字符串HTML反转义
        'unescape'      => 'html_entity_decode($0)',
        // 字符串URL转义
        'urlencode'     => 'urlencode($0)',
        // 字符串URL反转义
        'urldecode'     => 'urldecode($0)',
        // 数组转为JSON
        'jsonencode'    => 'json_encode($0, true)',
        // JSON转为数组
        'jsondenode'    => 'json_denode($0, JSON_UNESCAPED_UNICODE)',
        // 元素是否存在于数组作用
        'in'            => 'in_array($0, $1, true)',
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
        // 合并数组
        'merge'         => 'array_merge($0, $1)',
        // 最大值
        'max'           => 'max($0, ...)',
        // 最小值
        'min'           => 'min($0, ...)',
        // 转为数字
        'num'           => '($0+0)',
        // 数字绝对值
        'abs'           => 'abs($0)',
        // 数字向上取整
        'ceil'          => 'ceil($0)',
        // 数字向下取整
        'floor'         => 'floor($0)',
        // 数字四舍五入
        'round'         => 'round($0, ...)',
        // 数字随机值
        'rand'          => 'rand($0, $1)',
        // 数字格式化
        'number_format' => 'number_format($0)',
        // 时间戳
        'time'          => 'time()',
        // 转为时间戳
        'totime'        => 'strtotime($0)',
        // 时间格式化
        'date'          => 'date($0, $1)',
    ];
    
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::get('template')) {
            if ($filters = Arr::pull($config, 'filters')) {
                self::$filters = $filters + self::$filters;
            }
            self::$config = $config + self::$config;
        }
    }
    
    public static function complie($str, $is_tpl = false)
    {
        if ($is_tpl) {
            $str = self::$config['view_template_reader']($str);
        }
        $ret = '';
        $str = self::readInsertAndExtends($str, $ck);
        self::checkPhpSyntax($str);
        // 检查模版更新
        if ($ck) {
            $ret = self::wrapCode(self::$config['view_check_expired_macro'](array_unique($ck))).PHP_EOL;
        }
        $end = [];
        if ($res = self::parseTagWithText($str, self::$config['verbatim_tag'])) {
            $pos = 0;
            foreach ($res as $v) {
                $ret .= self::readStructAndText(substr($str, $pos, $v['pos'][0] - $pos), $end);
                $ret .= $v['text'];
                $pos  = $v['pos'][1];
            }
            $str = substr($str, $pos);
        }
        if (!$end) {
            return $ret.self::readStructAndText($str, $end);
        }
        throw new TemplateException('complie error: 结构语句标签未闭合');
    }
    
    /*
     * 检查PHP语法
     */
    protected static function checkPhpSyntax($str)
    {
        if (!self::$config['allow_php_syntax']) {
            $tokens = token_get_all($str);
            if (count($tokens) != 1 || $tokens[0][0] != T_INLINE_HTML) {
                throw new TemplateException('checkPhpSyntax error: 禁用PHP原生语法');
            }
        }
    }
    
    /*
     * 读取解析extends标签
     */
    protected static function readInsertAndExtends($str, &$ck = [])
    {
        $i = 0;
        $blocks = [];
        while (true) {
            $str = self::readInsert($str, $ck);
            if (!preg_match_all(self::tagRegex(self::$config['extends_tag']), $str, $matches, PREG_OFFSET_CAPTURE)) {
                break;
            }
            if (count($matches[0]) > 1) {
                throw new TemplateException('readIncludeAndExtends error: extends语句不允许有多条');
            }
            if ($i > 9) {
                throw new TemplateException('readIncludeAndExtends error: 嵌套不能大于9层，防止死循环');
            }
            $str = substr($str, 0, $matches[0][0][1]).substr($str, strlen($matches[0][0][0]));
            $name = self::parseSelfEndTagAttrs($matches[0][0][0], 'name');
            $ck[] = $name;
            // 读取子模版block
            if ($res = self::parseTagWithText($str, self::$config['block_tag'], 'name')) {
                $blocks += array_column($res, 'text', 'name');
            }
            $str = self::$config['view_template_reader']($name);
            $i++;
        }
        if ($i == 0) {
            return $str;
        }
        // 替换父模版block
        $str = preg_replace_callback(self::blockFuncRegex('block'), function ($matchs) use ($blocks) {
            return $blocks[$matchs[1]] ?? '';
        }, $str);
        if ($res = self::parseTagWithText($str, self::$config['block_tag'], 'name')) {
            $pos = 0;
            $ret = '';
            $regex = self::blockFuncRegex('parent', false);
            foreach ($res as $v) {
                $ret .= substr($str, $pos, $v['pos'][0] - $pos);
                $pos  = $v['pos'][1];
                if (isset($blocks[$v['name']])) {
                    $ret .= preg_replace_callback($regex, function ($matchs) use ($v) {
                        return $v['text'];
                    }, $blocks[$v['name']]);
                } else {
                    $ret .= $v['text'];
                }
            }
            return $ret.substr($str, $pos);
        }
        return $str;
    }
    
    /*
     * 读取解析include标签
     */
    protected static function readInsert($str, &$ck)
    {
        $i = 0;
        do {
            if ($i > 9) {
                throw new TemplateException('readInsert error: 嵌套不能大于9层，防止死循环');
            }
            $str = preg_replace_callback(self::tagRegex(self::$config['insert_tag']), function ($matches) use (&$ck) {
                $name = self::parseSelfEndTagAttrs($matches[0], 'name');
                $ck[] = $name;
                return self::$config['view_template_reader']($name);
            }, $str, -1, $count);
            $i++;
        } while ($count > 0);
        return $str;
    }
    
    /*
     * 读取解析插值语句
     */
    protected static function readStructAndText($str, &$end)
    {
        $l = preg_quote(self::$config['text_border_sign'][0]);
        $r = preg_quote(self::$config['text_border_sign'][1]);
        $p = preg_quote(self::$config['note_text_sign'].self::$config['verbatim_text_sign']);
        return preg_replace_callback("/([$p]?)$l(.*?)$r/", function ($matches) use ($str) {
            if ($s = $matches[1]) {
                // 忽略注释
                if ($s === self::$config['note_text_sign']) {
                    return '';
                // 不解析，原样输出
                } elseif($s === self::$config['verbatim_text_sign']) {
                    return substr($matches[0], strlen($s));
                }
            }
            $val = $matches[2];
            // 是否转义
            $escape = self::$config['auto_escape_text'];
            if (in_array($val[0], self::$config['text_escape_sign'])) {
                $escape = !array_search($val[0], self::$config['text_escape_sign']);
                $val = substr($val, 1);
            }
            $code = self::readValue($val);
            return self::wrapCode($escape ? "echo htmlentities($code);" : "echo $code;");
        }, self::readInclude(self::readStructTag($str, $end)));
    }
    
    /*
     * 读取解析require标签
     */
    protected static function readInclude($str)
    {
        return preg_replace_callback(self::tagRegex(self::$config['include_tag']), function ($matches) {
            $name  = self::$config['argument_attr_prefix'].'name';
            $attrs = self::parseSelfEndTagAttrs($matches[0]);
            if (isset($attrs['name'])) {
                $arg = $attrs['name'];
            } elseif(isset($attrs[$name])) {
                $arg = self::readValue($attrs[$name]);
            } else {
                throw new TemplateException("readInclude error: 标签值为空 $str");
            }
            return self::wrapCode(self::$config['view_include_macro']($arg, !isset($attrs['name'])));
        }, $str);
    }
    
    /*
     * 读取解析控制结构语句标签
     */
    protected static function readStructTag($str, &$end)
    {
        $v = '(?:"[^"]*"|\'[^\']*\')';
        $a = "(?:\s*\w+(?:\s*=\s*$v)?)";
        $s = preg_quote(self::$config['assign_attr_prefix']
                       .self::$config['struct_attr_prefix']
                       .self::$config['argument_attr_prefix']);
        $regex  = "/<(\w+)\s+$a*(?:\s*[$s]\w+(?:\s*=\s*$v)?)+$a*\s*\/?>/";
        if (!preg_match_all($regex, $str, $matches, PREG_OFFSET_CAPTURE)) {
            return $str;
        }
        $pos = 0;
        $ret = '';
        foreach ($matches[0] as $i => $match) {
            if ($pos > $match[1]) {
                throw new TemplateException("readTag error: 文本读取偏移地址错误");
            }
            // 读取左侧HTML
            $left  = substr($str, $pos, $match[1] - $pos);
            // 读取空白内容
            $blank = self::readLeftBlank($left);
            // 拼接左侧内容，如有为闭合语句，尝试闭合处理。
            $ret  .= $end ? self::completeStructTag($left, $end) : $left;
            // 读取解析标签内代码
            $attrs = self::readTagStructAttr($match[0]);
            if ($attrs['code']) {
                // 处理if与elseif else的衔接
                if ($attrs['is_else']) {
                    if (preg_match('/\?>\s*$/', $ret, $res)) {
                        $ret = substr($ret, 0, -strlen($res[0])).substr(implode(PHP_EOL.$blank, $attrs['code']), 6);
                    } else {
                        throw new TemplateException('readTag error: 衔接if else失败');
                    }
                } else {
                    $ret .= implode(PHP_EOL.$blank, $attrs['code']);
                }
            }
            // 补全空白内容
            $ret .= PHP_EOL.$blank;
            // 如果是空白标签则忽略标签HTML代码
            if ($matches[1][$i][0] != self::$config['blank_tag']) {
                $ret .= $attrs['html'];
            }
            // 如果是自闭合标签则自行添加PHP闭合，否则增加未闭合标签语句数据供下步处理。
            if ($attrs['count'] > 0) {
                if (substr($attrs['html'], -2) === '/>') {
                    $ret .= str_repeat(PHP_EOL.$blank.self::wrapCode('}'), $attrs['count']);
                } else {
                    $end[] = [
                        'num'   => 0, // HTML闭合标签层数
                        'tag'   => $matches[1][$i][0], // HTML标签名
                        'count' => $attrs['count'], // 补全的PHP闭合标签数
                    ];
                }
            }
            // 重设文本处理位置
            $pos = strlen($match[0]) + $match[1];
        }
        // 处理最后部分
        if ($tmp = substr($str, $pos)) {
            $ret.= $end ? self::completeStructTag($tmp, $end) : $tmp;
        }
        return $ret;
    }
    
    /*
     * 合并完成模版标签闭合
     */
    protected static function completeStructTag($str, &$end, $blank = null)
    {
        $ret = '';
        do {
            $i = count($end) - 1;
            $tag = $end[$i]['tag'];
            if (!preg_match_all("/(<$tag|<\/$tag>)/", $str, $matches, PREG_OFFSET_CAPTURE)) {
                // 无匹配则直接拼接剩余部分返回
                return $ret.$str;
            }
            $pos = 0;
            foreach ($matches[0] as $match) {
                $tmp  = substr($str, $pos, $match[1] - $pos);
                $ret .= $tmp;
                // 重设读取位置
                $pos  = strlen($match[0]) + $match[1];
                // 新开始标签则计数加一
                if ($match[0][1] !== '/') {
                    $ret .= $match[0];
                    $end[$i]['num']++;
                } else {
                    // 非最后结束标签则计数减一
                    if ($end[$i]['num'] > 0) {
                        $ret .= $match[0];
                        $end[$i]['num']--;
                    // 最后结束标签则处理标签闭合
                    } else {
                        if ($tag !== self::$config['blank_tag']) {
                            $ret .= $match[0];
                        }
                        $left = $blank ?? self::readLeftBlank($tmp);
                        $ret .= str_repeat(PHP_EOL.$left.self::wrapCode('}'), $end[$i]['count']);
                        // 处理完成，踢出当前任务，继续下一个任务。
                        array_pop($end);
                        break;
                    }
                }
            }
            $str = substr($str, $pos);
        } while ($i > 0);
        return $ret.$str;
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
        //$str     = self::readTagAttrMacro($str);
        $prefix  = preg_quote(self::$config['assign_attr_prefix'].self::$config['struct_attr_prefix']);
        $regex   = "/\s*([$prefix])(\w+)(?:\s*=\s*(\"[^\"]*\"|'[^']*'))?/";
        if (preg_match_all($regex, $str, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = 0;
            foreach ($matches[1] as $i => $match) {
                $html .= trim(substr($str, $pos, $matches[0][$i][1] - $pos));
                $pref  = $matches[1][$i][0];
                $name  = $matches[2][$i][0];
                if ($matches[3][$i]) {
                    $val = substr($matches[3][$i][0], 1, -1);
                } else {
                    if ($name != 'else' && $pref == self::$config['struct_attr_prefix']) {
                        throw new TemplateException("readTagAttr error: 标签属性值不能为空");
                    }
                }
                // 赋值语句
                if ($pref == self::$config['assign_attr_prefix']) {
                    $code[] = self::wrapCode("$$name = ".self::readValue($val).';');
                // 流程控制语句
                } else {
                    $count++;
                    if ($name == 'else' || $name == 'elseif') {
                        $is_else = true;
                        if ($code) {
                            throw new TemplateException("readTagAttr error: 单个标签内else或elseif前不允许有其它语句");
                        }
                    }
                    $code[] = self::wrapCode(self::readControlStruct($name, $val ?? null));
                }
                $pos = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            }
            $html .= substr($str, $pos);
        } else {
            $html = $str;
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
                return 'if ('.self::readValue($val).') {';
            case 'elseif':
                return 'elseif ('.self::readValue($val).') {';
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
                return 'foreach('.self::readValue($val)." as $as) {";
            case 'for':
                if (count($arr = explode(';', $attr['code'], 2)) == 2) {
                    foreach($arr as $v) {
                        $ret[] = self::readValue($v, $attr['vars']);
                    }
                    return 'for ('.implode(';', $ret).') {';
                }
                break;
            case 'while':
                return 'while ('.self::readValue($val).') {';
        }
        throw new TemplateException("readControlStruct error: 非法语句 $name ($val)");
    }
    
    /*
     * 解析标签属性
     */  
    protected static function parseSelfEndTagAttrs($str, $name = null, $self_end = true)
    {
        if ($self_end === true && substr($str, -2) != '/>') {
            throw new TemplateException("parseTagAttrs error: 必须自闭合标签 $str");
        }
        if ($self_end === false && substr($str, -2) == '/>') {
            throw new TemplateException("parseTagAttrs error: 必须非自闭合标签 $str");
        }
        $prefix = preg_quote(self::$config['argument_attr_prefix']);
        if (preg_match_all('/([\w-'.$prefix.']+)\s*=\s*(?:"([^"]+)"|\'([^\']+)\')/', $str, $matches)) {
            foreach ($matches[1] as $i => $attr) {
                $ret[$attr] = $matches[2][$i] ?: $matches[3][$i];
            }
            if (!$name) {
                return $ret;
            }
            if (isset($ret[$name])) {
                return $ret[$name];
            }
        }
        throw new TemplateException("parseSelfEndTagAttrs error: 标签值为空 $str");
    }
    
    /*
     * 
     */
    protected static function parseTagWithText($str, $tag, $name = null)
    {        
        if (!preg_match_all(self::tagRegex($tag), $str, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }
        $pos = 0;
        $end_tag = "</$tag>";
        foreach ($matches[0] as $i => $match) {
            if ($match[1] >= $pos) {
                if (!$pos = stripos($str, $end_tag, $match[1])) {
                    throw new TemplateException("parseTagWithText error: 标签 $tag 未闭合");
                }
                $start = $match[1] + strlen($match[0]);
                $ret[] = [
                    'pos'  => [$match[1], $pos + strlen($end_tag)],
                    'text' => substr($str, $start, $pos - $start),
                    'name' => $name ? self::parseSelfEndTagAttrs($match[0], $name, false) : null
                ];
            } else {
                throw new TemplateException("parseTagWithText error: 标签 $tag 不允许嵌套使用");
            }
        }
        return $ret;
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
     * 包装PHP代码
     */  
    protected static function wrapCode($code)
    {
        return "<?php $code ?>";
    }
    
    /*
     * 是否空白字符
     */ 
    protected static function isBlankChar($char)
    {
        return $char === ' ' || $char === "\t";
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
    protected static function blockFuncRegex($func, $has_arg = true)
    {
        $l = preg_quote(self::$config['text_border_sign'][0]);
        $r = preg_quote(self::$config['text_border_sign'][1]);
        return "/$l@\s*$func\(\s*".($has_arg ? '"(\w+)"' : '')."\s*\)\s*$r/";
    }
    
    /*
     * 读取语句单元
     */
    protected static function readValue($val, $strs = null, $end = null)
    {
        if ($strs === null) {
            extract(self::extractString($val));
        }
        if (preg_match('/\w\s+\w/', $val)) {
            throw new TemplateException("readValue error: 非法空格 $val");
        }
        // 去空格
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
                // 数组 过滤器
                case '.':
                    $ret = self::readArrayOrFilterValue($ret, $tmp, $val, $len, $i, $strs);
                    break;
                // 括号
                case '(':
                    if (!isset($ret)) {
                        if (isset($tmp)) {
                            $ret = self::readFilterValue($tmp, self::readArguments($val, $len, $i, $strs));
                        } elseif ($pos = self::findEndPos($val, $len, $i, '(', ')')) {
                            $ret = "(".self::readValue(substr($val, $i + 1, $pos - $i - 1), $strs).")";
                            $i = $pos;
                        }
                        break;
                    }
                    throw new TemplateException("readValue error: 非法 $c 语法");
                // 数组
                case '[':
                    if (!isset($tmp) && ($pos = self::findEndPos($val, $len, $i, '[', ']'))) {
                        if (isset($ret)) {
                            $key = self::readValue(substr($val, $i + 1, $pos - $i - 1), $strs);
                            $ret = $ret."[$key]";
                        } else {
                            $ret = self::readArrayValue(substr($val, $i, $pos + 1), $strs);
                        }
                        $i = $pos;
                        break;
                    }
                    throw new TemplateException("readValue error: 非法 $c 语法");
                // 数组
                case '{':
                    if (!isset($ret) && !isset($tmp) && ($pos = self::findEndPos($val, $len, $i, '{', '}'))) {
                        $ret = self::readArrayValue(substr($val, $i, $pos + 1), $strs);
                        $i = $pos;
                        break;
                    }
                    throw new TemplateException("readValue error: 非法 $c 语法");
                // 函数 静态方法 容器
                case '$':
                    if (!isset($ret) && !isset($tmp)) {
                        if (preg_match("/^\d+/", substr($val, $i + 1), $matchs)) {
                            $ret .= self::injectString('$'.$matchs[0], $strs);
                            $i += strlen($matchs[0]);
                        } else {
                            $ret = self::readFunctionValue($ret, $tmp, $val, $len, $i, $strs);
                        }
                        break;
                    }
                    throw new TemplateException("readValue error: 非法 $c 语法");
                // 三元表达式
                case '?':
                    $ret  = self::readInitValue($ret, $tmp);
                    $next = substr($val, $i + 1, 1);
                    if ($next == ':' || $next == '?') {
                        return "$ret ?$next ".self::readValue(substr($val, $i + 2), $strs);
                    } else {
                        return "$ret ? ".self::readValue(substr($val, $i + 1), $strs, ':');
                    }
                    throw new TemplateException("readValue error: 非法字符 $c");
                default:
                    if (in_array($c, ['+', '-', '*', '/', '%'])
                        || in_array($c, ['!', '&', '|', '=', '>', '<'])
                    ) {
                        // 对象操作符
                        if ($c == '-' && substr($val, $i + 1, 1) == '>') {
                            $ret = self::readObjectValue($ret, $tmp, $val, $len, $i, $strs);
                        } else {
                            $ret = self::readInitValue($ret, $tmp);
                            return "$ret $c ".self::readValue(substr($val, $i + 1), $strs);
                        }
                        break;
                    } elseif ($end === $c) {
                        return self::readInitValue($ret, $tmp)." $c ".self::readValue(substr($val, $i + 1), $strs);
                    }
                    throw new TemplateException("readValue error: 非法字符 $c");
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
     * 初始值
     */
    protected static function readInitValue($ret, $tmp)
    {
        if (isset($ret) && isset($tmp)) {
            throw new TemplateException("readInitValue error: $tmp");
        }
        return isset($tmp) ? self::readVarValue($tmp) : $ret;
    }
    
    /*
     * 数组
     */
    protected static function readArrayValue($val, $strs)
    {
        return "jsondecode('".self::injectString($val, $strs)."', true)";
    }
    
    /*
     * 小数 数组值 过滤器
     */
    protected static function readArrayOrFilterValue($ret, $tmp, $val, $len, &$pos, $strs)
    {
        if (!isset($ret) && !isset($tmp)) {
            throw new TemplateException("readArrayOrFilterValue error: 空属性");
        }
        // 小数
        if (isset($tmp) && is_numeric($tmp)) {
            if (preg_match('/^\d+/', substr($val, $pos + 1), $matches)) {
                $pos += strlen($matches[0]);
                return "$tmp.$matches[0]";
            }
            throw new TemplateException("readArrayOrFilterValue error: 无效小数");
        }
        $ret = self::readInitValue($ret, $tmp);
        $res = self::parseLastNameAndPos($val, $pos + 1, $len);
        $pos = $res['pos'];
        // 数组值
        if (empty($res['func'])) {
            return $ret."['".$res['name']."']";
        }
        // 宏函数
        return self::readFilterValue($res['name'], self::readArguments($val, $len, $pos, $strs, $ret ? [$ret] : []));
    }

    /*
     * 过滤器
     */
    protected static function readFilterValue($name, $args)
    {
        if (empty(self::$filters[$name])) {
            return self::$config['view_filter_macro']($name, $args);
        }
        if (is_callable(self::$filters[$name])) {
            return self::$filters[$name](...$args);
        }
        $ret = preg_replace_callback('/\\$(\d+)/', function ($matches) use (&$args) {
            if (isset($args[$matches[1]])) {
                $val = $args[$matches[1]];
                unset($args[$matches[1]]);
                return $val;
            }
            throw new TemplateException("readFilterValue error: 缺少参数");
        }, self::$filters[$name]);
        return preg_replace_callback('/\,\s*\.\.\.\s*\)\s*$/', function ($matches) use ($args) {
            return $args ? ','.implode(',', $args).')' : ')';
        }, $ret);
    }
    
    /*
     * 对象
     */
    protected static function readObjectValue($ret, $tmp, $val, $len, &$pos, $strs)
    {
        if (!isset($ret) && !isset($tmp)) {
            throw new TemplateException("readObjectValue error: 对象为空");
        }
        $ret = self::readInitValue($ret, $tmp);
        $res = self::parseLastNameAndPos($val, $pos + 2, $len);
        $pos = $res['pos'];
        if (empty($res['func'])) {
            return "$ret->".$res['name'];
        }
        $args = implode(', ', self::readArguments($val, $len, $pos, $strs));
        return "$ret->$res[name]($args)";
    }
    
    /*
     * 函数 静态方法 容器
     */
    protected static function readFunctionValue($ret, $tmp, $val, $len, &$pos, $strs)
    {
        $arr = [];
        $tmp = null;
        for ($i = $pos + 1; $i < $len; $i++) {
            $c = $val[$i];
            if (self::isVarChar($c)) {
                $tmp .= $c;
                continue;
            }
            if (!self::isVarChars($tmp)) {
                break;
            }
            $arr[] = $tmp;
            if ($c == '.') {
                $tmp = null;
            } elseif ($c == '(') {
                $pos  = $i;
                $args = implode(', ', self::readArguments($val, $len, $pos, $strs));
                // 函数
                if (count($arr) == 1) {
                    if (in_array($tmp, ['parent', 'block'])) {
                        throw new TemplateException("readFunctionValue error: $tmp 为特殊模版方法");
                    }
                    $fs = self::$config['allow_php_functions'] ?? null;
                    if ($fs == true || (is_array($fs) && in_array($tmp, $fs))) {
                        return "$tmp($args)";
                    }
                    throw new TemplateException("readFunctionValue error: 不支持的内置函数$tmp");
                }
                // 静态方法
                $ns = self::$config['view_class_namespace'] ?? null;
                if ($ns) {
                    $m  = array_pop($arr);
                    $ns = is_string($ns) ? "$ns\\" : '';
                    return $ns.implode('\\', $arr)."::$m($args)";
                }
                throw new TemplateException("readFunctionValue error: 未开启静态方法支持");
            } elseif ($c == '-' && substr($val, $i + 1, 1) == '>') {
                // 容器
                if (self::$config['view_container_macro']) {
                    $pos = $i - 1;
                    return self::$config['view_container_macro'](implode('.', $arr));
                }
                throw new TemplateException("readFunctionValue error: 未开启容器支持");
            } else {
                break;
            }
        }
        throw new TemplateException("readFunctionValue error: 解析错误");
    }
    
    /*
     * 参数
     */
    protected static function readArguments($val, $len, &$pos, $strs, $args = [])
    {
        $epos = self::findEndPos($val, $len, $pos, '(', ')');
        if ($tmp = trim(substr($val, $pos + 1, $epos - $pos - 1))) {
            foreach (explode(',', $tmp) as $v) {
                $args[] = self::readValue(trim($v), $strs);
            }
        }
        $pos = $epos;
        return $args;
    }
    
    /*
     *  解析最后的名字和位置
     */
    protected static function parseLastNameAndPos($val, $pos, $len)
    {
        $ret = null;
        for ($i = $pos; $i < $len; $i++) {
            $c = $val[$i];
            if (!self::isVarChar($c)) {
                break;
            }
            $ret .= $c;
        }
        if (self::isVarChars($ret)) {
            if (substr($val, $i, 1) !== '(') {
                return ['name' => $ret, 'pos' => $i - 1];
            }
            return ['name' => $ret, 'pos' => $i, 'func' => true];
        }
        throw new TemplateException("readItemVal error: 非法字符 $ret");
    }
    
    /*
     * 注入字符串
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
            if ($c === '"' || $c === "'") {
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
     * 是否变量名字符
     */ 
    protected static function isVarChar($char)
    {
        return preg_match('/\w/', $char);
    }
    
    /*
     * 是否变量名字符串
     */ 
    protected static function isVarChars($str)
    {
        if(($len = strlen($str)) > 0 && !in_array($str, ['true', 'false', 'null']) && !is_numeric($str[0])) {
            for ($i = 0; $i < $len; $i++) {
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
