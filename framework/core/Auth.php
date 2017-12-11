<?php
namespace framework\core;

use framework\App;

abstract class Auth
{
    private static $init;
    private static $auth;
    
    // 获取认证用户ID
    abstract protected function id();
    
    // 获取认证用户信息
    abstract protected function user();
    
    // 检查用户是否认证通过
    abstract protected function check();
    
    // 用户认证失败处理
    abstract protected function fallback();
    
    // 登记用户信息
    abstract protected function login($user);
    
    // 注销用户信息
    abstract protected function logout();
    
    /*
     * 初始化
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('auth');
        if (is_subclass_of($config['class'], __CLASS__)) {
            self::$auth = (new $config['class']($config));
        } else {
            throw new Exception('Illegal auth class');
        }
        Event::on('exit', __CLASS__.'::free');
    }
    
    /*
     * 静态调用
     */
    public static function __callStatic($method, $params)
    {
        return self::$auth->$method(...$params);
    }
    
    // 运行认证处理，检查用户是否认证成功，否则失败处理并退出
    public static function run()
    {
        self::$auth->check() || self::$auth->fallback() || App::exit();
    }
    
    // 清除
    public static function free()
    {
        self::$auth = null;
    }
}
Auth::init();
