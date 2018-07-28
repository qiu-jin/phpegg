<?php
namespace framework\core;

class Dispatcher
{
    /*
     * 路由调度
     */
    public static function route($path, $rules, $param_mode, $dynamic = false, $method = null)
    {
        $route = (new Router($path, $method))->route($rules);
        return $route && self::dispatch($route, $param_mode, $dynamic);
    }
    
    /*
     * 解析调度
     */
    public static function dispatch($route, $param_mode = 0, $dynamic = false)
    {
        if (($lpos = strpos($route['dispatch'], '(')) && ($rpos = strpos($route['dispatch'], ')'))) {
            $dispatch = substr($route['dispatch'], 0, $lpos);
            $param_name = substr($route['dispatch'], $lpos + 1, $rpos - $lpos);
        } else {
            $dispatch = $route['dispatch'];
        }
        $params = $route['matches'];
        if $dynamic && strpos('$', $dispatch) !== false) {
            $dispatch = self::dynamicDispatch($dispatch, $params);
        }
        if ($param_mode && $params && isset($param_name)) {
            if ($param_mode === 2) {
                $params = self::bindKvParams($param_name, $params);
            } else {
                $params = self::bindListParams($param_name, $params);
            }
        } 
        return [$dispatch, $params];
    }
    
    /*
     * 获取动态调用
     */
    public static function dynamicDispatch($dispatch, $params)
    {
        return preg_replace_callback('/\$(\d)/', $dispatch, function ($match) use ($params) {
            if (isset($params[$match[1]])) {
                return $params[$match[1]];
            }
            throw new \Exception("Illegal dynamic Dispatch: $dispatch");
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
