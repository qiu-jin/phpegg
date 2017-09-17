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
    public static function dispatch($path, $ruotes, $method = null)
    {
        $result = self::route($path, $ruotes, $method);
        if ($result) {
            list($call, $macth) = $result;
            $pair = explode('?', $call, 2);
            $unit = explode('/', $pair[0]);
            if (count($macth) > 0) {
                foreach ($unit as $i => $v) {
                    if ($v[0] == '$' && is_numeric($v[1])) $unit[$i] = $macth[$v[1]-1];
                }
                if (isset($pair[1])) {
                    parse_str($pair[1],$param);
                    if (count($param) > 0) {
                        foreach ($param as $k => $v) {
                            if ($v[0] == '$' && is_numeric($v[1])) {
                                $params[$k] = $macth[$v[1]-1];
                            } else {
                                $params[$k] = $v;
                            }
                        }
                    }
                } else {
                    $params = $macth;
                }
            }
            return [$unit, $params ?? []];
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
                if ($count === count($rule)) {
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
        }
        return false;
    }
    
    /*
     * 路由规则匹配
     */
    public static function macth( $path, $rule)
    {
        $macth = [];
        foreach ($rule as $i => $unit) {
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
                        $macth[] = array_slice($path, $i);
                        return $macth;
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
}
