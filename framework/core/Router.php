<?php
namespace framework\core;

class Router
{
    // 过滤器
    private static $filters = [
        'id' => 'Validator::id',
        'hash' => 'Validator::hash',
        'email' => 'Validator::email',
        'mobile' => 'Validator::mobile',
    ];

    /*
     * 路由调度
     */
    public static function dispatch($path, $ruotes, $method = null)
    {
        if (isset($ruotes['/'])) {
            $index_ruote = $ruotes['/'];
            unset($ruotes['/']);
        } 
        if (empty($path)) {
            return isset($index_ruote) ? [explode('/', trim($rule)), ['/', $rule], []] : false;
        }
        if ($ruotes) {
            $count = count($path);
            foreach ($ruotes as $rule => $to) {
                $rule = explode('/', trim($rule, '/'));
                if ($count === count($rule)) {
                    $macth = self::macth($path, $rule);
                    if ($macth !== false) {
                        if (is_array($to)) {
                            if (isset($to[$method])) {
                                $to = $to[$method];
                            } else {
                                return null;
                            }
                        }
                        $pairs = explode('?', $to, 2);
                        $call = explode('/', $pairs[0]);
                        if (count($call) > 0) {
                            foreach ($call as $i => $v) {
                                if ($v[0] == '$' && is_numeric($v[1])) $call[$i] = $macth[$v[1]-1];
                            }
                            $params = [];
                            if (isset($pairs[1])) {
                                parse_str($pairs[1],$param);
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
                                $params = [];
                            }
                            return [$call, $params, [$rule => $to]];
                        }
                    }
                }
            }
        }
        return false;
    }
    
    /*
     * 路由规则匹配
     */
    public static function macth($rule, $path)
    {
        $macth = [];
        foreach ($rule as $i => $unit) {
            switch ($unit[0]) {
                case '*':
                    if (strlen($unit) === 1) {
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
                    $unit = substr($unit, 1, -1);
                    if ($unit && preg_match('/^'.$unit.'$/i', $path[$i], $matchs)) {
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
                    return false;
                case '~':
                    $macth[] = array_slice($path, $i);
                    break 2;
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
