<?php
namespace framework\core;

use framework\util\Arr;
use framework\core\http\Request;
use framework\exception\TemplateException;

class Template
{
    protected static $init;
    // 配置
    protected static $config    = [
        // 空标签
        'void_tag'              => 'void',
        // 原生标签
        'raw_tag'               => 'raw',
        // php标签
        'php_tag'               => 'php',
        // 注释标签
        'note_tag'              => 'note',
        // heredoc标签
        'heredoc_tag'           => 'heredoc',
        // 插槽标签
        'slot_tag'              => 'slot',
        // 块标签
        'block_tag'             => 'block',
        // 父标签
        'parent_tag'            => 'parent',
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
        // 双引号语句前缀符
        'double_attr_prefix'    => '*',
        // 参数语句前缀符
        'argument_attr_prefix'  => ':',
        // 文本插入左右边界符号
        'text_delimiter_sign'   => ['{{', '}}'],
        // 文本插入是否自动转义
        'auto_escape_text'      => true,
        // 文本转义符号与反转义符号
        'text_escape_sign'      => [':', '!'],
        // 不输出标识符
        'not_echo_text_sign'    => '#',
        // 允许的函数
        'allow_php_functions'   => false,
        // 允许的静态类
        'allow_static_classes'  => false,
        // 允许的容器
        'allow_container_providers' => false,
        // 是否去除原生HTML注释
        'remove_html_note' 			=> false,
        // filter宏
        'view_filter_macro'         => __CLASS__.'::filterMacro',
        // include宏
        'view_include_macro'        => __CLASS__.'::includeMacro',
        // container宏
        'view_container_macro'      => __CLASS__.'::ContainerMacro',
        // check expired宏
        'view_check_expired_macro'  => __CLASS__.'::checkExpiredMacro',
        // 模版读取器
        'view_template_reader'      => View::class.'::readTemplate',
    ];
    // 魔术变量
    protected static $vars    = [
		'input'     => Request::class.'::input',
        'query'     => Request::class.'::query',
        'param'     => Request::class.'::param',
        'cookie'    => Request::class.'::cookie',
        'header'    => Request::class.'::header',
        'server'    => Request::class.'::server',
		'session'   => Request::class.'::session',
    ];
    // 内置函数
    protected static $filters = [
        // 是否存在
        'isset'         => 'isset($0)',
        // 是否为空
        'empty'         => 'empty($0)',
        // 默认值
        'default'       => '($0 ?? $1)',
        // 转为字符串
        'str'           => 'strval($0)',
        // 字符串拼接
        'cat'           => '($0.$1)',
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
        'slice'         => 'substr($0, $1, $2 > 0 ? $2 - $1 : $2)',
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
        'to_time'       => 'strtotime($0)',
        // 时间格式化
        'date'          => 'date($0, $1)',
        // 视图文件是否存在
        'view_exists'   => View::class.'::exists($0)',
    ];
    
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::get('template')) {
            if ($vars = Arr::pull($config, 'vars')) {
                self::$vars = $vars + self::$vars;
            }
            if ($filters = Arr::pull($config, 'filters')) {
                self::$filters = $filters + self::$filters;
            }
            self::$config = $config + self::$config;
        }
    }
    
    /*
     * 编译模版
     */
    public static function complie($str, $is_tpl = false)
    {
        if ($is_tpl) {
            $str = self::$config['view_template_reader']($str);
        }
        $ret = '';
        $str = self::removeNote($str);
        $str = self::readInsertAndExtends($str, $ck);
        self::checkPHPSyntax($str);
        // 检查模版更新
        if ($ck) {
            $ret = self::wrapCode(self::$config['view_check_expired_macro'](array_unique($ck))).PHP_EOL;
        }
        return $ret.self::readRawAndPHPCode($str);
    }
    
    /*
     * 检查PHP语法
     */
    protected static function checkPHPSyntax($str)
    {
        $tokens = token_get_all($str);
        if (count($tokens) != 1 || $tokens[0][0] != T_INLINE_HTML) {
            throw new TemplateException('禁用PHP原生语法');
        }
    }
    
    /*
     * 解析去除模版注释
     */
    protected static function removeNote($str)
    {
		// 去除原生注释
		if (self::$config['remove_html_note']) {
			$str = preg_replace('/\<\!--[\w\W]*?--\>/', '', $str);
		}
		// 去除模版注释
		$ret = '';
        if ($res = self::parseTagWithText($str, self::$config['note_tag'], null, false, true)) {
            $pos = 0;
            foreach ($res as $v) {
                $ret .= substr($str, $pos, $v['pos'][0] - $pos);
                $pos  = $v['pos'][1];
            }
            $str = substr($str, $pos);
        }
        return $ret.$str;
    }
    
    /*
     * 读取解析extends标签
     */
    protected static function readInsertAndExtends($str, &$ck = [])
    {
        $i = 0;
        $blocks = [];
        while (true) {
            if ($i > 9) {
                throw new TemplateException('嵌套不能大于9层，防止死循环');
            }
            $str = self::readInsert($str, $ck);
            if (!preg_match_all(self::tagRegex(self::$config['extends_tag']), $str, $matches, PREG_OFFSET_CAPTURE)) {
                break;
            }
            if (count($matches[0]) > 1) {
                throw new TemplateException('extends语句不允许有多条');
            }
            $str = substr($str, 0, $matches[0][0][1]).substr($str, strlen($matches[0][0][0]));
            $name = self::parseSelfEndTagAttrs($matches[0][0][0], 'name');
            $ck[] = $name;
            // 读取子模版block
            if ($res = self::parseTagWithText($str, self::$config['block_tag'], 'name')) {
                $blocks += array_column($res, 'text', 'attr');
            }
            $str = self::removeNote(self::$config['view_template_reader']($name));
            $i++;
        }
        if ($i == 0) {
            return $str;
        }
        // 替换父模版block
        if ($res = self::parseTagWithText($str, self::$config['block_tag'], 'name', null)) {
            $pos = 0;
            $ret = '';
            foreach ($res as $v) {
                $ret .= substr($str, $pos, $v['pos'][0] - $pos);
                $pos  = $v['pos'][1];
                if (isset($blocks[$v['attr']]) || !isset($v['text'])) {
                    $ret .= preg_replace_callback("/<".self::$config['parent_tag']."\s*\/>/", function () use ($v) {
                        return $v['text'];
                    }, $blocks[$v['attr']]);
                } else {
                    $ret .= $v['text'];
                }
            }
            return $ret.substr($str, $pos);
        }
        return $str;
    }
    
    /*
     * 读取解析insert标签
     */
    protected static function readInsert($str, &$ck)
    {
        $i = 0;
        while (true) {
            if ($i > 9) {
                throw new TemplateException('嵌套不能大于9层，防止死循环');
            }
            if (!$res = self::parseTagWithText($str, self::$config['insert_tag'], 'name', null)) {
                break;
            }
            $pos = 0;
            $ret = '';
            foreach ($res as $v) {
                $ret .= substr($str, $pos, $v['pos'][0] - $pos);
                $name = $v['attr'];
                if (strpos($name, '.')) {
                    list($name, $block) = explode('.', $name, 2);
                }
                $ck[] = $name;
                $content = $contents[$name] ??
                           $contents[$name] = self::removeNote(self::$config['view_template_reader']($name));
                if (isset($block)) {
                    if (!isset($block_contents[$name])) {
                        $result = self::parseTagWithText($content, self::$config['block_tag'], 'name');
                        $blocks[$name] = array_column($result, 'text', 'attr');
                    }
                    if (!isset($blocks[$name][$block])) {
                        throw new TemplateException("$name.$block block 不存在");
                    }
                    $content = $blocks[$name][$block];
                }
                $slots = null;
                if ($v['text'] && ($sres = self::parseTagWithText($v['text'], self::$config['slot_tag'], 'name'))) {
                    $spos = 0;
                    $sret = '';
                    foreach ($sres as $sv) {
                        $sret .= substr($content, $spos, $sv['pos'][0] - $spos);
                        $spos  = $sv['pos'][1];
                    }
                    $v['text'] = $sret.substr($v['text'], $spos);
                    $slots = array_column($sres, 'text', 'attr');
                }
                if ($sres = self::parseTagWithText($content, self::$config['slot_tag'], null, null)) {
                    $spos = 0;
                    $sret = '';
                    foreach ($sres as $sv) {
                        $sret .= substr($content, $spos, $sv['pos'][0] - $spos);
                        $spos  = $sv['pos'][1];
                        if (isset($sv['attr']['name'])) {
                            $sret .= $slots[$sv['attr']['name']] ?? $sv['text'];
                        } else {
                            $sret .= $v['text'] ?? $sv['text'];
                        }
                    }
                    $ret .= $sret.substr($content, $spos);
                } else {
                    $ret .= $content;
                }
                $pos  = $v['pos'][1];
            }
            $str = $ret.substr($str, $pos);
            $i++;
        }
        return $str;
    }
    
    /*
     * 读取解析原生语句
     */
    protected static function readRawAndPHPCode($str)
    {
        $ret = '';
        $end = [];
        if ($res = self::parseTagWithText($str, self::$config['raw_tag'], null, false, true)) {
            $pos = 0;
            foreach ($res as $v) {
                $ret .= self::readPHPCodeAndHeredoc(substr($str, $pos, $v['pos'][0] - $pos), $end);
                $ret .= $v['text'];
                $pos  = $v['pos'][1];
            }
            $str = substr($str, $pos);
        }
        if ($str) {
            $ret .= self::readPHPCodeAndHeredoc($str, $end);
        }
        if (!$end) {
            return $ret;
        }
        throw new TemplateException('结构语句标签未闭合');
    }
    
    /*
     * 读取解析php代码
     */
    protected static function readPHPCodeAndHeredoc($str, &$end)
    {
        if (!self::$config['php_tag']) {
            return self::readHeredocAndMore($str, $end);
        }
        $ret = '';
        if ($res = self::parseTagWithText($str, self::$config['php_tag'])) {
            $pos = 0;
            foreach ($res as $v) {
                $ret .= self::readHeredocAndMore(substr($str, $pos, $v['pos'][0] - $pos), $end);
                $ret .= self::wrapCode(PHP_EOL.$v['text'].PHP_EOL);
                $pos  = $v['pos'][1];
            }
            $str = substr($str, $pos);
        }
        if ($str) {
            $ret.= self::readHeredocAndMore($str, $end);
        }
        return $ret;
    }
    
    /*
     * 读取解析heredoc
     */
    protected static function readHeredocAndMore($str, &$end)
    {
        if (!self::$config['heredoc_tag']) {
            return self::readStructAndText($str, $end);
        }
        $ret = '';
        if ($res = self::parseTagWithText($str, self::$config['heredoc_tag'])) {
            $pos = 0;
            foreach ($res as $v) {
                $eot  = $v['attr']['name'] ?? 'EOT';
                $ret .= self::readStructAndText(substr($str, $pos, $v['pos'][0] - $pos), $end);
                $ret .= self::wrapCode("print <<<$eot".PHP_EOL.$v['text'].PHP_EOL."$eot;");
                $pos  = $v['pos'][1];
            }
            $str = substr($str, $pos);
        }
        if ($str) {
            $ret.= self::readStructAndText($str, $end);
        }
        return $ret;
    }
    
    /*
     * 读取解析插值语句
     */
    protected static function readStructAndText($str, &$end)
    {
        $l = preg_quote(self::$config['text_delimiter_sign'][0]);
        $r = preg_quote(self::$config['text_delimiter_sign'][1]);
        return preg_replace_callback("/$l(.*?)$r/", function ($matches) use ($str) {
            $val = $matches[1];
			if ($val[0] == self::$config['not_echo_text_sign']) {
				// 不输出
				return self::wrapCode(self::readValue(substr($val, 1)).';');
			} else {
                // 是否转义
                $escape = self::$config['auto_escape_text'];
                if (in_array($val[0], self::$config['text_escape_sign'])) {
                    $escape = !array_search($val[0], self::$config['text_escape_sign']);
                    $val = substr($val, 1);
                }
                $code = self::readValue($val);
                return self::wrapCode($escape ? "echo htmlspecialchars($code);" : "echo $code;");
			}
        }, self::readInclude(self::readStructTag($str, $end)));
    }
    
    /*
     * 读取解析include标签
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
                throw new TemplateException("标签值为空 $str");
            }
            return self::wrapCode(self::$config['view_include_macro']($arg, !isset($attrs['name'])));
        }, $str);
    }
    
    /*
     * 读取解析控制结构语句标签
     */
    protected static function readStructTag($str, &$end)
    {
        $v = "\"[^\"]*\"|'[^']*'";
        $regex  = "/<([\w-]+)\s+[".self::attrPrefixRegex()."][\w-]+(\s*=\s*($v))?($v|[^'\">])*>/";
        if (!preg_match_all($regex, $str, $matches, PREG_OFFSET_CAPTURE)) {
            return $str;
        }
        $pos = 0;
        $ret = '';
        foreach ($matches[0] as $i => $match) {
            if ($pos > $match[1]) {
                throw new TemplateException("文本读取偏移地址错误");
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
                        throw new TemplateException('衔接if else失败');
                    }
                } else {
                    $ret .= implode(PHP_EOL.$blank, $attrs['code']);
                }
            }
            // 补全空白内容
            $ret .= PHP_EOL.$blank;
            if ($matches[1][$i][0] != self::$config['void_tag']) {
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
                // 无匹配则直接返回
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
                        if ($tag !== self::$config['void_tag']) {
                            $ret .= $match[0];
                        }
                        $left = $blank ?? self::readLeftBlank($tmp);
                        $ret .= str_repeat(PHP_EOL.$left.self::wrapCode('}'), $end[$i]['count']);
                        // 处理完成，退出当前，继续下一环。
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
        $regex   = "/\s*([".self::attrPrefixRegex()."])([\w-]+)(?:\s*=\s*(\"[^\"]*\"|'[^']*'))?/";
        if (preg_match_all($regex, $str, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = 0;
            foreach ($matches[1] as $i => $match) {
                $html .= substr($str, $pos, $matches[0][$i][1] - $pos);
                $pref  = $matches[1][$i][0];
                $name  = $matches[2][$i][0];
                if ($matches[3][$i]) {
                    $val = substr($matches[3][$i][0], 1, -1);
                } else {
                    if ($name != 'else' && $pref == self::$config['struct_attr_prefix']) {
                        throw new TemplateException("标签属性值不能为空");
                    }
                }
                // 
                if ($pref == self::$config['double_attr_prefix']) {
                    $q = $matches[3][$i][0][0];
                    $e = self::$config['auto_escape_text'];
                    $html .= " $name =$q".self::wrapCode($e ? "echo htmlspecialchars(\"$val\");" : "echo \"$val\";").$q;
                // 赋值语句
                } elseif ($pref == self::$config['assign_attr_prefix']) {
                    $code[] = self::wrapCode("$$name = ".self::readValue($val).';');
                // 流程控制语句
                } elseif ($pref == self::$config['struct_attr_prefix']) {
                    $count++;
                    if ($name == 'else' || $name == 'elseif') {
                        $is_else = true;
                        if ($code) {
                            throw new TemplateException("单个标签内else或elseif前不允许有其它语句");
                        }
                    }
                    $code[] = self::wrapCode(self::readControlStruct($name, $val ?? null));
                } else {
                    $html .= $matches[0][$i][0];
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
        throw new TemplateException("非法语句 $name ($val)");
    }
    
    /*
     * 解析标签属性
     */  
    protected static function parseSelfEndTagAttrs($str, $name = true, $self_end = true)
    {
        if ($self_end === true && substr($str, -2) != '/>') {
            throw new TemplateException("必须自闭合标签 $str");
        }
        if ($self_end === false && substr($str, -2) == '/>') {
            throw new TemplateException("必须非自闭合标签 $str");
        }
        $prefix = preg_quote(self::$config['argument_attr_prefix']);
        if (preg_match_all('/([\w-'.$prefix.']+)\s*=\s*(?:"([^"]+)"|\'([^\']+)\')/', $str, $matches)) {
            foreach ($matches[1] as $i => $attr) {
                $ret[$attr] = $matches[2][$i] ?: $matches[3][$i];
            }
            if ($name === true || $name === null) {
                return $ret;
            }
            if (isset($ret[$name])) {
                return $ret[$name];
            }
        }
        if ($name === null) {
            return null;
        }
        throw new TemplateException("标签值为空 $str");
    }
    
    /*
     *  解析标签文本
     */
    protected static function parseTagWithText($str, $tag, $name = null, $self_end = false, $nesting = false)
    {        
        if (!preg_match_all(self::tagRegex($tag), $str, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }
        $pos = 0;
        $end_tag = "</$tag>";
        foreach ($matches[0] as $i => $match) {
            if ($match[1] >= $pos) {
                $attr = self::parseSelfEndTagAttrs($match[0], $name, $self_end);
                if (substr($match[0], -2) === '/>') {
                    $ret[] = [
                        'pos'  => [$match[1], $match[1] + strlen($match[0])],
                        'text' => null,
                        'attr' => $attr
                    ];
                } else {
                    if (!$pos = stripos($str, $end_tag, $match[1])) {
                        throw new TemplateException("标签 $tag 未闭合");
                    }
                    $start = $match[1] + strlen($match[0]);
                    $ret[] = [
                        'pos'  => [$match[1], $pos + strlen($end_tag)],
                        'text' => substr($str, $start, $pos - $start),
                        'attr' => $attr
                    ];
                }
            // 允许嵌套
            } elseif ($nesting) {
                $p = $pos;
                $n = count($ret) - 1;
                if (!$pos = stripos($str, $end_tag, $ret[$n]['pos'][1])) {
                    throw new TemplateException("标签 $tag 未闭合");
                }
                $ret[$n]['pos'][1] = $pos + strlen($end_tag);
                $ret[$n]['text'] .=  substr($str, $p, $pos - $p);
            } else {
                throw new TemplateException("标签 $tag 不允许嵌套使用");
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
     * 属性正则前缀
     */
    protected static function attrPrefixRegex()
    {
        return preg_quote(self::$config['assign_attr_prefix'].self::$config['struct_attr_prefix']
                         .self::$config['double_attr_prefix'].self::$config['argument_attr_prefix']);
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
            throw new TemplateException("非法空格 $val");
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
                    throw new TemplateException("非法 $c 语法 $val");
                // 数组
                case '[':
                    if ($pos = self::findEndPos($val, $len, $i, '[', ']')) {
                        if (!isset($ret) && !isset($tmp)) {
                            $ret = self::readArrayValue(substr($val, $i, $pos + 1), $strs);
                        } else {
                            $key = self::readValue(substr($val, $i + 1, $pos - $i - 1), $strs);
                            if (isset($ret) && !isset($tmp)) {
                                $ret = $ret."[$key]";
                            } elseif (!isset($ret) && isset($tmp)) {
                                $ret = self::readInitValue($ret, $tmp)."[$key]";
                            } else {
                                throw new TemplateException("非法 $c 语法 $val");
                            }
                        }
                        $i = $pos;
                        break;
                    }
                    throw new TemplateException("数组结束符号缺失 $val");
                // 数组
                case '{':
                    if (!isset($ret) && !isset($tmp) && ($pos = self::findEndPos($val, $len, $i, '{', '}'))) {
                        $ret = self::readArrayValue(substr($val, $i, $pos + 1), $strs);
                        $i = $pos;
                        break;
                    }
                    throw new TemplateException("非法 $c 语法 $val");
                // 函数 静态方法 容器
                case '@':
                    if (!isset($ret) && !isset($tmp)) {
                        $ret = self::readFunctionValue($ret, $tmp, $val, $len, $i, $strs);
                        break;
                    }
                    throw new TemplateException("非法 $c 语法 $val");
                // 特殊变量
                case '$':
                    if (!isset($ret) && !isset($tmp)) {
                        if ($ret = self::readSpecialVarValue($val, $len, $i, $strs)) {
                            break;
                        }
                    }
                    throw new TemplateException("非法 $c 语法 $val");
                // 三元表达式
                case '?':
                    $ret  = self::readInitValue($ret, $tmp);
                    $next = substr($val, $i + 1, 1);
                    if ($next == ':' || $next == '?') {
                        return "$ret ?$next ".self::readValue(substr($val, $i + 2), $strs);
                    } else {
                        return "$ret ? ".self::readValue(substr($val, $i + 1), $strs, ':');
                    }
                    throw new TemplateException("非法 $c 语法 $val");
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
                    throw new TemplateException("非法 $c 字符 $val");
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
        return is_numeric($var) || in_array($var, ['true', 'false', 'null']) ? $var : '$'.$var;
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
        return "json_decode('".self::injectString($val, $strs)."', true)";
    }
    
    /*
     * 特殊变量
     */
    protected static function readSpecialVarValue($val, $len, &$pos, $strs)
    {
        $str = substr($val, $pos + 1);
        // 临时变量
        if (preg_match("/^\d/", $str, $matchs)) {
            $pos += 1;
            return self::injectString('$'.$matchs[0], $strs);
        // 魔术变量
        } elseif (preg_match('/^(?:'.implode('|', array_keys(self::$vars)).')/', $str, $matchs)) {
            $i = $pos + strlen($matchs[0]) + 1;
            $ret = self::$vars[$matchs[0]];
            switch ($c = substr($val, $i, 1)) {
                case '.':
                    $res = self::parseLastNameAndPos($val, $i + 1, $len);
                    $pos = $res['pos'];
                    if (empty($res['func'])) {
                        return "$ret('$res[name]')";
                    }
                    return self::readFilterValue($res['name'], self::readArguments($val, $len, $pos, $strs, ["$ret()"]));
                case '[':
                    $epos = self::findEndPos($val, $len, $i, '[', ']');
                    $pos = $epos;
                    return "$ret(".self::readValue(substr($val, $i + 1, $epos - $i - 1), $strs).")";
                default:
                    if (!self::isVarChar($c)) {
                        $pos = $i;
                        return "$ret()";
                    }
            }
        }
    }
    
    /*
     * 小数 数组值 过滤器
     */
    protected static function readArrayOrFilterValue($ret, $tmp, $val, $len, &$pos, $strs)
    {
        if (!isset($ret) && !isset($tmp)) {
            throw new TemplateException("空属性");
        }
        // 小数
        if (isset($tmp) && is_numeric($tmp)) {
            if (preg_match('/^\d+/', substr($val, $pos + 1), $matches)) {
                $pos += strlen($matches[0]);
                return "$tmp.$matches[0]";
            }
            throw new TemplateException("无效小数");
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
        if (is_string(self::$filters[$name])) {
            $ret = preg_replace_callback('/\\$(\d+)/', function ($matches) use (&$args) {
                if (isset($args[$matches[1]])) {
                    $val = $args[$matches[1]];
                    unset($args[$matches[1]]);
                    return $val;
                }
                throw new TemplateException("缺少参数");
            }, self::$filters[$name]);
            return preg_replace_callback('/\,\s*\.\.\.\s*\)\s*$/', function ($matches) use ($args) {
                return $args ? ','.implode(',', $args).')' : ')';
            }, $ret);
        }
        if (is_callable(self::$filters[$name])) {
            return self::$filters[$name](...$args);
        }
        throw new TemplateException("无效的filter设置");
    }
    
    /*
     * 对象
     */
    protected static function readObjectValue($ret, $tmp, $val, $len, &$pos, $strs)
    {
        if (!isset($ret) && !isset($tmp)) {
            throw new TemplateException("对象为空");
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
                throw new TemplateException("非法名称 $tmp");
            }
            $arr[] = $tmp;
            if ($c == '.') {
                $tmp = null;
            } elseif ($c == '(') {
                $pos  = $i;
                $args = implode(', ', self::readArguments($val, $len, $pos, $strs));
                // 函数
                if (count($arr) == 1) {
                    if (self::$config['allow_php_functions'] === true
                        || in_array($tmp, self::$config['allow_php_functions'])
                    ) {
                        return "$tmp($args)";
                    }
                    throw new TemplateException("不支持的内置函数$tmp");
                }
                // 静态方法
                $tmp = [];
                while ($v = array_pop($arr)) {
                    $tmp[] = $v;
                    $class = implode('\\', $arr);
                    if (isset(self::$config['allow_static_classes'][$class])) {
                        $m = array_shift($tmp);
                        $n = $tmp ? '\\'.implode('\\', array_reverse($tmp)) : '';
                        return self::$config['allow_static_classes'][$class]."$n::$m($args)";
                    }
                }
                throw new TemplateException("未定义的静态类$class");
            } else {
				$end = true;
                break;
            }
        }
		if ($tmp) {
            // 容器
			if (!isset($end)) {
				$arr[] = $tmp;
			}
            $provider = implode('.', $arr);
            if (self::$config['allow_container_providers'] === true
                || in_array($provider, self::$config['allow_container_providers'])
            ) {
                $pos = $i - 1;
                return self::$config['view_container_macro']($provider);
            }
            throw new TemplateException("不支持的容器$provider");
		}
        throw new TemplateException("解析错误");
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
        throw new TemplateException("非法字符 $ret");
    }
    
    /*
     * 注入字符串
     */
    protected static function injectString($val, $strs)
    {
        return preg_replace_callback('/\\$(\d)/', function ($matches) use ($strs) {
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
        throw new TemplateException("字符串引号未闭合");
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
        throw new TemplateException("$val 没有找到结束符 $right");
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
    
    /*
     *  模版编译宏（依赖框架实现）
     */
    protected static function filterMacro($name, $args)
    {
        return View::class."::callFilter('$name'".($args ? ', '.implode(', ', $args) : '').")";
    }
    
    protected static function ContainerMacro($name)
    {
        return Container::class."::make('$name')";
    }
    
    protected static function includeMacro($name, $is_var = false)
    {
        return 'include '.View::class.'::path('.($is_var ? $name : "'$name'").');';
    }
    
    protected static function checkExpiredMacro($names)
    {
        return 'if ('.View::class."::checkExpired(__FILE__, '".implode("', '", $names)."')) return include __FILE__;";
    }
}
Template::__init();
