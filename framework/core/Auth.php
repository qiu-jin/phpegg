<?php
namespace framework\core;

use framework\App;

abstract class Auth
{
    private static $init;
    // 实例
    private static $auth;
    // 用户信息
    protected $user;
    
    /*
     * 验证用户
     */
    abstract protected function auth();
    
    /*
     * 用户认证失败后续操作
     */
    abstract protected function fallback();
    
    /*
     * 登记用户信息
     */
    abstract protected function signin($user);
    
    /*
     * 注销用户信息
     */
    abstract protected function signout();
    
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        $config = Config::flash('auth');
        if (!is_subclass_of($config['class'], __CLASS__)) {
            throw new Exception('Illegal auth class');
        }
        self::$auth = (new $config['class']($config));
        if (!empty($config['auto_auth'])) {
            self::$auth->user = self::$auth->auth();
        }
        Event::on('exit', [__CLASS__, 'clean']);
    }
    
    /*
     * 静态调用
     */
    public static function __callStatic($method, $params)
    {
        return self::$auth->$method(...$params);
    }
    
    /*
     * 获取用户id
     */
    public static function id()
    {
        return self::$auth->user['id'];
    }
    
    /*
     * 获取用户信息
     */
    public static function user()
    {
        return self::$auth->user;
    }
    
    /*
     * 登录
     */
    public static function login($user, $temp = false)
    {
        if (!$temp) {
            self::$auth->login($user);
        }
        self::$auth->user = $user;
    }
    
    /*
     * 登出
     */
    public static function logout($temp = false)
    {
        if (!$temp) {
            self::$auth->signout();
        }
        self::$auth->user = null;
    }
    
    /*
     * 检查用户认证
     */
    public static function check()
    {
        return isset(self::$auth->user) || (self::$auth->user = self::$auth->auth());
    }
    
    /*
     * 运行认证处理，检查用户是否认证成功，否则失败处理并退出
     */
    public static function run()
    {
        $this->check() || self::$auth->fallback() === true || App::exit();
    }
    
    /*
     * 清理
     */
    public static function clean()
    {
        self::$auth = null;
    }
}
Auth::__init();
