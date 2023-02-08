<?php
namespace framework\core;


class Dispatcher
{
    /*
     * 路由调度
     */
    public static function route($path, $rules, $method = null)
    {
        if ($route = (new Router($path, $method))->route($rules)) {
            return self::dispatch($route['dispatch'], $route['matches']);
        }
    }
    
    /*
     * 获取调度信息
     */
    public static function dispatch($dispatch, $params = null)
    {
        $dispatch = self::parseDispatch($dispatch, $param_names);
        if ($params && $param_names) {
            $params = self::bindParams($param_names, $params);
        } 
        return [$dispatch, $params];
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
     * 解析参数
     */
    protected static function bindParams($rules, $params)
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
