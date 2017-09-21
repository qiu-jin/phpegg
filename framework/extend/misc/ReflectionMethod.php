<?php
namespace framework\extend\misc;

class ReflectionMethod 
{
    public static function bindListParams($ref_method, $params, $to_null = false)
    {
        $method_cou = count($params);
        $method_num = $ref_method->getnumberofparameters();
        if ($method_num > $method_cou) {
            $parameters = $ref_method->getParameters();
            for ($i = $method_cou; $i < $method_num; $i++) {
                if ($parameters[$i]->isDefaultValueAvailable()) {
                    $params[] = $parameters[$i]->getdefaultvalue();
                } elseif ($to_null) {
                    $params[] = null;
                } else {
                    return false;
                }
            }
        }
        return $params;
    }
    
    public static function bindkvParams($ref_method, $params, $to_null = false)
    {
        $new_params = [];
        if ($ref_method->getnumberofparameters() > 0) {
            foreach ($ref_method->getParameters() as $param) {
                if (isset($params[$param->name])) {
                    $new_params[] = $params[$param->name];
                } elseif($param->isDefaultValueAvailable()) {
                    $new_params[] = $param->getdefaultvalue();
                } elseif ($to_null) {
                    $new_params[] = null;
                } else {
                    return false;
                }
            }
        }
        return $new_params;
    }
}
