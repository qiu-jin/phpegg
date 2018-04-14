<?php
namespace framework\core;

class Dispatcher
{
    /*
     * 路由调度
     */
    public static function route($path, $rules, $param_mode, $dynamic = false, $method = null)
    {
        $router = new framework\core\Router($path, $method);
        return ($route = $router->route($rules)) && self::dispatch($route, $param_mode, $dynamic);
    }
    
    /*
     * 解析调度
     */
    public static function dispatch($route, $param_mode = 0, $dynamic = false)
    {
        if (preg_match('/^([^\(]+)(\(([^\)]+)\))?$/', $route['dispatch'], $res)) {
            $call   = $res[1];
            $params = $route['matches'];
            if $dynamic && strpos('$', $call) !== false) {
                $call = self::dynamicCall($call, $params);
            }
            if ($param_mode && $params && isset($res[3])) {
                if ($param_mode === 2) {
                    $params = self::bindKvParams($res[3], $params);
                } else {
                    $params = self::bindListParams($res[3], $params);
                }
            } 
            return [$call, $params];
        }
        throw new \Exception("Illegal dispatch role: ".$route['dispatch']);
    }
    
    /*
     * 获取动态调用
     */
    public static function dynamicCall($call, $params)
    {
        return preg_replace_callback('/\$(\d)/', $call, function ($res) use ($params) {
            if (isset($params[$res[1]])) {
                return $params[$res[1]];
            }
            throw new \Exception("Illegal dynamic Dispatch: $call");
        });
    }
    
    /*
     * 解析kv参数
     */
    protected static function bindKvParams($rules, $params)
    {
        foreach (explode(',', $rules) as $rule) {
            list($k, $v) = explode('=', trim($rule));
            $k = trim($k);
            $v = trim($v);
            if ($v[0] === '$') {
                $index = substr($v, 1) - 1;
                if (isset($matches[$index])) {
                    $ret[$k] = $matches[$index];
                } else {
                    $ret[$k] = null;
                }
            } else {
                $ret[$k] = json_decode($v, true);
            }
        }
        return $ret ?? [];
    }
    
    /*
     * 解析list参数
     */
    protected static function bindListParams($rules, $params)
    {
        foreach (explode(',', $rules) as $rule) {
            $rule = trim($rule);
            if ($rule[0] === '$') {
                $index = substr($rule, 1) - 1;
                if (isset($matches[$index])) {
                    $ret[] = $matches[$index];
                } else {
                    $ret[] = null;
                }
            } else {
                $ret[] = json_decode($rule, true);
            }
        }
        return $ret ?? [];
    }
}
