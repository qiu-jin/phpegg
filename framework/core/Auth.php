<?php
namespace framework\core;

use framework\App;

abstract class Auth
{
    private static $auth;
    
    protected function __construct(){}
    
    public static function init()
    {
        if (self::$auth) return;
        $config = Config::get('auth');
        if (is_subclass_of($config['class'], __CLASS__)) {
            self::$auth = (new $config['class']($config));
        } else {
            throw new Exception('Illegal auth class');
        }
        Hook::add('exit', __CLASS__.'::free');
    }
    
    // 获取认证用户ID
    abstract public function id();
    
    // 获取认证用户信息
    abstract public function user();
    
    // 检查用户是否认证成功
    abstract public function check();
    
    // 用户认证失败处理
    abstract public function fallback();
    
    // 登记用户信息
    abstract public function login();
    
    // 注销用户信息
    abstract public function logout();
    
    // 运行认证处理，检查用户是否认证成功，否则失败处理并退出
    public function run()
    {
        $this->check() || $this->fallback() || App::exit();
    }
    
    // 获取运行认证实例
    public static function instance()
    {
        return self::$auth;
    }
    
    // 清除
    public static function free()
    {
        self::$auth = null;
    }
}
Auth::init();
