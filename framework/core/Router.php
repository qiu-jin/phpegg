<?php
namespace framework\core;

class Router
{
    private static $init;
    // 内置正则规则
    private static $patterns;
    
    // 路径数组
    private $path;
    // 路径数组count
    private $count;
    // HTTP方法
    private $method;

    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        self::$patterns = Config::get('router.patterns');
    }
    
    public static function setPattern($name, $value)
    {
        self::$patterns[$name] = $value;
    }
    
    public static function setPatterns($values)
    {
        self::$patterns = self::$patterns ? $value + self::$patterns : $value;
    }

    public function __construct($path, $method = null)
    {
        $this->path   = $path;
        $this->count  = count($path);
        $this->method = $method;
    }

    public function route($rules, $step = 0)
    {
        if (!$rules) {
            return false;
        }
        // 数组树尾梢为dispatch数据
        if (!is_array($rules)) {
            return ['dispatch' => $rules];
        }
        foreach ($rules as $k => $v) {
            if (($matches = self::match($k, $step)) !== false
                // 递归处理
                && ($route = self::route($v, $matches[1]))
            ) {
                if ($matches[0]) {
                    if (empty($route['matches'])) {
                        $route['matches'] = $matches[0];
                    } else {
                        $route['matches'] = array_merge($matches[0], $route['matches']);
                    }
                }
                return $route;
            }
        }
        return false;
    }
    
    private function match($rule, $step)
    {
        $ret = [];
        // 空匹配
        if ($rule === '/') {
            return $step === $this->count ? [$ret, $step] : false;
        }
        foreach (explode('/', $rule) as $v) {
            $c = $v[0];
            $s = $this->path[$step] ?? null;
            if ($s) {
                if ($c === '?') {
                    $v = substr($v, 1);
                    $c = $v[0];
                }
            } else{
                if ($c === '?') {
                    return [$ret, $step];
                } elseif ($c !== ':') {
                    return false;
                }
            }
            switch ($c) {
                // 通配
                case '*':
                    if ($v === '*') {
                        $ret[] = $s;
                        break;
                    }
                    return false;
                // 用户正则匹配
                case '(':
                    if (substr($v, -1) === ')'
                        && ($n = substr($v, 1, -1))
                        && preg_match("/^$n$/", $s, $m)
                    ) {
                        if (count($m) > 1) {
                            $ret = array_merge($ret, array_slice($m, 1)),
                        } else {
                            $ret[] = $s;
                        }
                        break;
                    }
                    return false;
                // 内置正则匹配
                case '[':
                    if (substr($v, -1) === ']'
                        && ($n = substr($v, 1, -1))
                        && isset(self::$patterns[$n])
                        && preg_match('/^'.self::$patterns[$n].'$/', $s, $m)
                    ) {
                        if (count($m) > 1) {
                            $ret = array_merge($ret, array_slice($m, 1)),
                        } else {
                            $ret[] = $s;
                        }
                        break;
                    }
                    return false;
                // 验证器匹配
                case '{':
                    if (substr($v, -1) === '}'
                        && ($n = substr($v, 1, -1))
                        && \Validator::{$n}($s)
                    ) {
                        $ret[] = $s;
                        break;
                    }
                    return false;
                // 匹配剩余
                case '~':
                    if ($v === '~') {
                        return [
                            array_merge($ret, array_slice($this->path, $step)),
                            $this->count
                        ];
                    }
                    return false;
                // 匹配HTTP方法
                case ':':
                    foreach (explode(' ', substr($v, 1)) as $n) {
                        if (trim($n) === $this->method) {
                            return [$ret, $step];
                        }
                    }
                    return false;
                // 原义匹配
                case '!':
                    if ($s === substr($v, 1)) {
                        break;
                    }
                    return false;
                // 原义匹配
                default:
                    if ($s === $v) {
                        break;
                    }
                    return false;
            }
            $step++;
        }
        return [$ret, $step];
    }
}
Router::__init();
