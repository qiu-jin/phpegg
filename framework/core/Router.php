<?php
namespace framework\core;

class Router
{
    private static $init;
    // 过滤器
    private static $regex_filters;
    private static $callable_filters = [
        'id' => 'Validator::id',
        'hash' => 'Validator::hash',
        'email' => 'Validator::email',
        'mobile' => 'Validator::mobile',
    ];
    private static $enable_dynamic_call;
    
    /*
     * 初始方法
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        if ($config = Config::get('router')) {
            if (isset($config['regex_filters'])) {
                self::$regex_filters = $config['regex_filters'];
            }
            if (isset($config['callable_filters'])) {
                self::$callable_filters = array_merge(self::$callable_filters, $config['callable_filters']);
            }
            self::$enable_dynamic_call = $config['enable_dynamic_call'] ?? false;
        }
    }
    
    /*
     * 路由调度
     */
    public static function dispatch($path, $ruotes, $param_mode = 0, $method = null)
    {
        $result = self::route($path, $ruotes, $method);
        return $result ? self::parse($reslut, $param_mode) : false;
    }
    
    /*
     * 解析调度
     */
    public static function parse($result, $param_mode = 0)
    {
        list($call_role, $macth) = $result;
        if (!preg_match('/^(.+?)(\((.*?)\))?$/', $call_role, $call_match)) {
            throw new Exception("Illegal route call role $call_role");
        }
        $call = $call_match[1];
        if (self::$enable_dynamic_call && strpos('$', $call) !== false) {
            $call = self::replaceDynamicCall($call, $macth);
        }
        if (!$param_mode || empty($call_match[3])) {
            $params = $macth;
        } else {
            $param_method = $param_mode === 2 ? 'parseKvParams' : 'parseListParams';
            $params = self::{$param_method}($call_match[3], $macth);
        }
        return [$call, $params];
    }
    
    /*
     * 路由处理
     */
    public static function route($path, $ruotes, $method = null)
    {
        if (empty($ruotes)) {
            return false;
        }
        if (isset($ruotes['/'])) {
            if (empty($path)) {
                $call = self::getCall($method, $ruotes['/']);
                if ($call) {
                    return [$call, []];
                }
            }
            unset($ruotes['/']);
        }
        $count = count($path);
        foreach ($ruotes as $rule => $calls) {
            $rule = explode('/', $rule);
            $macth = self::macth($path, $rule);
            if ($macth !== false) {
                $call = self::getCall($method, $calls);
                return $call ? [$call, $macth] : false;
            }
        }
        return false;
    }
    
    /*
     * 路由匹配
     */
    public static function macth($path, $rule)
    {
        $macth = [];
        foreach ($rule as $i => $unit) {
            if ($unit[0] === '[') {
                if (isset($path[$i])) {
                    $unit = substr($unit, 1, -1);
                } else {
                    break;
                }
            } else {
                if (!isset($path[$i])) {
                    return false;
                }
            }
            switch ($unit[0]) {
                case '*':
                    if ($unit === '*') {
                        $macth[] = $path[$i];
                        break;
                    }
                    return false;
                case ':':
                    if ($name = substr($unit, 1)) {
                        if (isset(self::$callable_filters[$name]) && call_user_func(self::$callable_filters[$name], $path[$i])) {
                            $macth[] = $path[$i];
                            break;
                        }
                    }
                    return false;
                case '{':
                    if (substr($unit, -1) === '}') {
                        $name = substr($unit, 1, -1);
                        if (isset(self::$regex_filters[$name]) && preg_match('/^'.self::$regex_filters[$name].'$/i', $path[$i], $matchs)) {
                            $macth[] = $path[$i];
                            break;
                        }
                    }
                    return false;
                case '(':
                    if (substr($unit, -1) === ')') {
                        $regex = substr($unit, 1, -1);
                        if ($regex && preg_match('/^'.$regex.'$/i', $path[$i], $matchs)) {
                            $count = count($matchs);
                            if ($count > 1) {
                                for ($j = 1; $j < $count; $j++) {
                                    $macth[] = $matchs[$j];
                                }
                            } else {
                                $macth[] = $path[$i];
                            }
                            break;
                        }
                    }
                    return false;
                case '~':
                    if ($unit === '~') {
                        return array_merge($macth, array_slice($path, $i));
                    }
                    return false;
                default:
                    if ($path[$i] === $unit && !empty($unit)) break;
                    return false;
            }
        }
        return $macth;
    }
    
    /*
     * 添加正则过滤器
     */
    public static function addRegexFilter($name, $regex)
    {
        self::$regex_filters[$name] = $regex;
    }
    
    /*
     * 添加函数方法过滤器
     */
    public static function addCallableFilter($name, callable $call)
    {
        self::$callable_filters[$name] = $call;
    }
    
    /*
     * 获取调用
     */
    protected static function getCall($method, $calls)
    {
        if ($method === null || !is_array($calls)) {
            return $calls;
        } elseif(isset($calls[$method])) {
            return $calls[$method];
        }
        return false;
    }
    
    /*
     * 获取动态调用
     */
    protected static function replaceDynamicCall($call, $macth)
    {
        return preg_replace_callback('/\$(\d)/', $call, function ($macthes) use ($macth) {
            if (isset($macth[$macthes[1]])) {
                return $macth[$macthes[1]];
            }
            throw new Exception('Illegal Dynamic Call');
        });
    }
    
    /*
     * 解析list参数
     */
    protected static function parseListParams($rules, $macth)
    {
        $params = [];
        foreach (explode(',', $rules) as $rule) {
            $rule = trim($rule);
            if ($rule[0] === '$') {
                $index = substr($rule, 1)-1;
                if (isset($macth[$index])) {
                    $params[] = $macth[$index];
                } 
            }
        }
        return $params;
    }
    
    /*
     * 解析kv参数
     */
    protected static function parseKvParams($rules, $macth)
    {
        $params = [];
        foreach (explode(',', $rules) as $rule) {
            list($k, $v) = explode('=', trim($rule));
            $k = trim($k);
            $v = trim($v);
            if ($v[0] === '$') {
                $index = substr($v, 1)-1;
                if (isset($macth[$index])) {
                    $params[$k] = $macth[$index];
                } else {
                    break;
                }
            } else {
                $params[$k] = $v;
            }
        }
        return $params;
    }
}
