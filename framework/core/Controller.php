<?php
namespace framework\core;

class Controller
{
    public static function methodBindKvParams(\ReflectionMethod $reflection_method, $params, $default_null = false)
    {
        if ($reflection_method->getnumberofparameters() > 0) {
            foreach ($reflection_method->getParameters() as $param) {
                if (isset($params[$param->name])) {
                    $new_params[] = $params[$param->name];
                } elseif($param->isDefaultValueAvailable()) {
                    $new_params[] = $param->getdefaultvalue();
                } elseif ($default_null) {
                    $new_params[] = null;
                } else {
                    return false;
                }
            }
        }
        return $new_params ?? [];
    }
    
    public static function methodBindListParams(\ReflectionMethod $reflection_method, $params, $default_null = false)
    {
        $count = count($params);
        $number = $reflection_method->getnumberofparameters();
        if ($number > $count) {
            $parameters = $reflection_method->getParameters();
            for ($i = $count; $i < $number; $i++) {
                if ($parameters[$i]->isDefaultValueAvailable()) {
                    $params[] = $parameters[$i]->getdefaultvalue();
                } elseif ($default_null) {
                    $params[] = null;
                } else {
                    return false;
                }
            }
        }
        return $params;
    }
}
