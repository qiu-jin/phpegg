<?php
namespace framework\core;

class Router
{
    private static $init;
    
    private static $cache;
    // 过滤器
    private static $filters = [
        'id' => 'Validator::id',
        'hash' => 'Validator::hash',
        'email' => 'Validator::email',
        'mobile' => 'Validator::mobile',
    ];
    private static $replace_call = false;
    
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('router');
        if (isset($config['filters'])) {
            self::$filters = array_merge(self::$filters, $config['filters']);
        }
    }
    
    /*
     * 路由调度
     */
    public static function dispatch($path, $ruotes, $param_mode = 0, $method = null)
    {
        $result = self::route($path, $ruotes, $method);
        if ($result) {
            list($call_role, $macth) = $result;
            if (preg_match('/^(.+?)(\((.*?)\))?$/', $call_role, $call_match)) {
                $call = self::$replace_call ? self::replaceCall($call_match[1], $macth) : $call_match[1];
                if (!$param_mode || empty($call_match[3])) {
                    $params = $macth;
                } else {
                    $param_method = $param_mode === 2 ? 'parseKvParams' : 'parseListParams';
                    $params = self::{$param_method}($call_match[3], $macth);
                }
                return [$call, $params];
            }
            throw new Exception('Illegal start call');
        }
        return false;
    }

    public static function route($path, $ruotes, $method = null)
    {
        if (isset($ruotes['/'])) {
            $index_ruote = $ruotes['/'];
            unset($ruotes['/']);
        } 
        if (empty($path)) {
            if (isset($index_ruote)) {
                if (is_array($index_ruote)) {
                    return isset($index_ruote[$method]) ? [$index_ruote[$method], []] : false;
                } else {
                    return [$index_ruote, []];
                }
            }
            return false;
        }
        if ($ruotes) {
            $count = count($path);
            foreach ($ruotes as $rule => $call) {
                $rule = explode('/', $rule);
                $macth = self::macth($path, $rule);
                if ($macth !== false) {
                    if (is_array($call)) {
                        if (isset($call[$method])) {
                            $call = $call[$method];
                        } else {
                            return false;
                        }
                    }
                    return [$call, $macth];
                }
            }
        }
        return false;
    }
    
    /*
     * 路由规则匹配
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
                    if ($name = substr($unit,1)) {
                        if (isset($this->filters[$name]) && call_user_func($this->filters[$name], $path[$i])) {
                            $macth[] = $path[$i];
                            break;
                        }
                    }
                    return false;
                case '(':
                    if (substr($unit, -1) === ')') {
                        $reg = substr($unit, 1, -1);
                        if ($reg && preg_match('/^'.$reg.'$/i', $path[$i], $matchs)) {
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
     * 添加过滤器
     */
    public static function addFilter($name, $filter)
    {
        self::$filters[$name] = $filter;
    }
    
    protected static function replaceCall($call, $macth)
    {
        return preg_replace_callback('/\$(\d)/', $call, function ($macthes) use ($macth) {
            if (isset($macth[$macthes[1]])) {
                return $macth[$macthes[1]];
            }
            throw new Exception('Illegal start call');
        });
    }
    
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
