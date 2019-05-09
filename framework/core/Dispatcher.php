<?php
namespace framework\core;

use framework\util\Arr;

class Dispatcher
{
    /*
     * 路由调度
     */
    public static function route($path, $rules, $param_mode, $dynamic_dispatch = false, $method = null)
    {
        if ($route = (new Router($path, $method))->route($rules)) {
            return self::dispatch($route, $param_mode, $dynamic_dispatch);
        }
    }
    
    /*
     * 获取调度信息
     */
    public static function dispatch($data, $param_mode = 0, $dynamic_dispatch = false)
    {
        $params = $data['matches'];
        $dispatch = self::parseDispatch($data['dispatch'], $param_names);
		$is_dynamic = false;
        if ($dynamic_dispatch && strpos($dispatch, '$') !== false) {
            $dispatch = self::dynamicDispatch($dispatch, $params, $is_dynamic);
        }
        if ($param_mode && $params && $param_names) {
            if ($param_mode === 2) {
                $params = self::bindKvParams($param_names, $params);
            } else {
                $params = self::bindListParams($param_names, $params);
            }
        } 
        return [$dispatch, $params, $is_dynamic, $data['next'] ?? null];
    }
    
    /*
     * 解析
     */
    public static function parseDispatch($dispatch, &$param_names = null)
    {
        if (($lpos = strpos($dispatch, '(')) && ($rpos = strpos($dispatch, ')'))) {
            $param_names = substr($dispatch, $lpos + 1, $rpos - $lpos - 1);
            return substr($dispatch, 0, $lpos);
        }
        return $dispatch;
    }
    
    /*
     * 获取动态调用
     */
    public static function dynamicDispatch($dispatch, &$params, &$is_dynamic)
    {
        return preg_replace_callback('/\$(\d)/', function ($match) use ($dispatch, &$params, &$is_dynamic) {
            $i = $match[1] - 1;
            if (isset($params[$i])) {
				$is_dynamic = true;
				return Arr::pull($params, $i);
            }
            throw new \Exception("Illegal dynamic Dispatch: $dispatch");
        }, $dispatch);
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
                if (isset($params[$index])) {
                    $ret[$k] = $params[$index];
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
                if (isset($params[$index])) {
                    $ret[] = $params[$index];
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
