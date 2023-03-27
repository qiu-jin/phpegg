<?php
namespace framework\core;

class Router
{
    private static $init;
    // 内置正则规则
    private static $patterns = [];
    
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
		if ($config = Config::get('router')) {
			if (isset($config['patterns'])) {
				self::$patterns = $config['patterns'];
			}
		}
    }
	
    /*
     * 设置内置正则规则
     */
	public static function pattern($name, $value = null)
	{
		if (isset($value)) {
			self::$patterns[$name] = $value;
		} else {
			self::$patterns = $name + self::$patterns;
		}
	}
	
    /*
     * 构造函数
     */
    public function __construct(array $path, $method = null)
    {
        $this->path   = $path;
        $this->count  = count($path);
        $this->method = $method;
    }

    /*
     * 路由匹配
     */
    public function route($rules, $step = 0)
    {
        if (!$rules) {
            return false;
        }
        // 数组树尾梢为dispatch数据
        if (!is_array($rules)) {
            if ($this->count != $step) {
                return false;
            }
            return ['dispatch' => $rules];
        }
        foreach ($rules as $k => $v) {
            if (($matches = self::match($k, $step)) !== false
                // 递归处理
                && ($route = self::route($v, $matches[1]))
            ) {
                if (empty($route['matches'])) {
                    $route['matches'] = $matches[0];
                } elseif ($matches[0]) {
                    $route['matches'] = array_merge($matches[0], $route['matches']);
                }
				if (isset($matches[2])) {
					$route['next'] = $matches[2];
				}
                return $route;
            }
        }
        return false;
    }
    
    /*
     * 匹配单元
     */
    public function match($rule, $step)
    {
        $ret = [];
		// 方法匹配
		if ($rule[0] == ':') {
			$arr = explode(' ', substr($rule, 1), 2);
			$method = strtoupper(trim($arr[0]));
			if (strpos($method, '|') === false) {
				if ($method != $this->method) {
					return false;
				}
			} elseif (!in_array($this->method, explode('|', $method))) {
				return false;
			}
			if (isset($arr[1])) {
				$rule = trim($arr[1]);
			} else {
				return [$ret, $step];
			}
		}
        // 空匹配
        if ($rule == '/') {
            return $step == $this->count ? [$ret, $step] : false;
        }
        foreach (explode('/', $rule) as $v) {
            $c = $v[0];
			if (isset($this->path[$step])) {
				$s = $this->path[$step];
				// 可选匹配
                if ($c == '?') {
                    $v = substr($v, 1);
                    $c = $v[0];
                }
            } else{
                if ($c == '?') {
                    return [$ret, $step];
				} elseif ($c == '~') {
					return [$ret, $step, []];
                } elseif ($c != ':') {
                    return false;
                }
            }
            switch ($c) {
                // 通配
                case '*':
                    if ($v == '*') {
                        $ret[] = $s;
                        break;
                    }
                    return false;
                // 用户正则匹配
                case '(':
                    if (substr($v, -1) == ')'
                        && ($n = substr($v, 1, -1))
                        && preg_match("/^$n$/", $s, $m)
                    ) {
                        if (isset($m[1])) {
                            $ret = array_merge($ret, array_slice($m, 1));
                        } else {
                            $ret[] = $s;
                        }
                        break;
                    }
                    return false;
                // 内置正则匹配
                case '[':
                    if (substr($v, -1) == ']'
                        && ($n = substr($v, 1, -1))
                        && isset(self::$patterns[$n])
                        && preg_match('/^'.self::$patterns[$n].'$/', $s, $m)
                    ) {
                        if (isset($m[1])) {
                            $ret = array_merge($ret, array_slice($m, 1));
                        } else {
                            $ret[] = $s;
                        }
                        break;
                    }
                    return false;
                // 验证器匹配
                case '{':
                    if (substr($v, -1) == '}'
                        && ($n = substr($v, 1, -1))
                        && Validator::{$n}($s)
                    ) {
                        $ret[] = $s;
                        break;
                    }
                    return false;
                // 余部匹配
                case '~':
                    if ($v === '~') {
                        return [$ret, $this->count, array_slice($this->path, $step)];
                    }
                    return false;
                // 原义匹配
                case '!':
                    if ($s == substr($v, 1)) {
                        break;
                    }
                    return false;
                // 默认匹配
                default:
                    if ($s == $v) {
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
