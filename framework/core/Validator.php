<?php
namespace framework\core;

use framework\App;

class Validator
{
    private $rule;
    private $error;
    private $message = [
        'require'   => ':name require',
        'id'        => ':name must be id',
        'ip'        => ':name must be ip',
        'email'     => ':name must be email',
        'mobile'    => ':name must be mobile'
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
    
    public function check(array $data, $fall_continue = false)
    {
        foreach ($this->rule as $name => $rule) {
            if (!isset($data[$name])) {
                $data[$name] = null;
            }
            foreach (explode('|', $rule) as $item) {
                $params = explode(':', $item);
                $method = array_shift($params);
                if (!self::{$method}($data[$name], ...$params)) {
                    $this->error[$name] = $thus->message[$method];
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
    
    public function run()
    {
        $this->check() || self::$auth->fallback() === true || App::exit();
    }
    
    public function error()
    {
        return $this->error;
    }
    
    public static function require($var)
    {
        return isset($var);
    }

    public static function id($var)
    {
        return is_numeric($var) && is_int($var + 0) && $var > 0;
    }
    
    public static function ip($var)
    {
        return filter_var($var, FILTER_VALIDATE_IP);
    }
    
    public static function url($var)
    {
        return filter_var($var, FILTER_VALIDATE_URL);
    }
    
    public static function email($var)
    {
        return filter_var($var, FILTER_VALIDATE_EMAIL);
    }
    
    public static function mobile($var)
    {
        return preg_match('/^1[34578]\d{9}$/', $var);
    }
    
    public static function min($var, $min)
    {
        return strlen($var) >= $min;
    }
    
    public static function max($var, $max)
    {
        return strlen($var) <= $max;
    }

    public static function between($var, $min, $max)
    {
        $len = strlen($var);
        return $len >= $min && $len <= $max;
    }
}
