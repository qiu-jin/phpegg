<?php
namespace framework\core;

use framework\App;

class Validator
{
    protected $data;
    protected $rule;
    protected $error;
    protected $message = [
        'require'   => '{name} require',
        'id'        => '{name} must be id',
        'ip'        => '{name} must be ip',
        'email'     => '{name} must be email',
        'mobile'    => '{name} must be mobile'
    ];
    
    public function __construct($rule, array $message = null)
    {
        if ($rule) {
            $this->rule = $rule;
        }
        if ($message) {
            $this->message = $message + $this->message;
        }
    }
    
    public function check($fall_continue = false)
    {
        foreach ($this->rule as $name => $rule) {
            if (!isset($data[$name])) {
                $data[$name] = null;
            }
            foreach (explode('|', $rule) as $item) {
                $params = explode(':', $item);
                $method = array_shift($params);
                if (!self::{'check'.$method}($data[$name], ...$params)) {
                    $this->error[$name] = strtr($this->message[$method], ['{name}' => $name]);
                    if (!$fall_continue) {
                        return false;
                    }
                    break;
                }
            }
        }
        return !isset($this->error);
    }
    
    public function fallback()
    {
        App::abort(400, $this->error);
    }
    
    public function run($data)
    {
        $this->check($data) || $this->fallback() === true || App::exit();
    }
    
    public function error()
    {
        return $this->error;
    }
    
    public static function validate($value, $rule)
    {

    }
    
    public static function checkRequire($var)
    {
        return isset($var);
    }

    public static function checkId($var)
    {
        return is_numeric($var) && is_int($var + 0) && $var > 0;
    }
    
    public static function checkIp($var)
    {
        return filter_var($var, FILTER_VALIDATE_IP);
    }
    
    public static function checkUrl($var)
    {
        return filter_var($var, FILTER_VALIDATE_URL);
    }
    
    public static function checkEmail($var)
    {
        return filter_var($var, FILTER_VALIDATE_EMAIL);
    }
    
    public static function checkMobile($var)
    {
        return preg_match('/^1[3456789]\d{9}$/', $var);
    }
    
    public static function checkMin($var, $min)
    {
        return strlen($var) >= $min;
    }
    
    public static function checkMax($var, $max)
    {
        return strlen($var) <= $max;
    }

    public static function checkBetween($var, $min, $max)
    {
        $len = strlen($var);
        return $len >= $min && $len <= $max;
    }
}
