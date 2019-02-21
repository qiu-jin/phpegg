<?php
namespace framework\core;

use framework\util\File;
use framework\core\misc\ViewError;
use framework\core\exception\ViewException;

class View
{    
    private static $init;
    // 数据
    private static $view;
    // 配置
    private static $config = [
        // 视图文件扩展名
        'ext'       => '.php',
        // 视图文件目录
        'dir'       => APP_DIR.'view/',
        // 错误页面
        'error'     => null,
        // 是否清理数据
        'clean'     => true,
        // 模版配置
        'template'  => [
            // 模版文件扩展名
            'ext'       => '.html',
            // 模版文件目录，为空则默认为视图文件目录
            'dir'       => null,
            // 调试模式下强制编译模版
            'debug'     => APP_DEBUG,
            // 模版引擎，为空则不启用模版
            'engine'    => Template::class,
        ]
    ];

    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::get('view')) {
            self::$config = array_replace_recursive(self::$config, $config);
        }
        if (self::$config['clean']) {
            Event::on('exit', [__CLASS__, 'clean']);
        }
    }
    
    /*
     * 设置变量
     */
    public static function var($name, $value)
    {
        self::$view['vars'][$name] = $value;
    }
    
    public static function vars(array $values)
    {
        self::$view['vars'] = isset(self::$view['vars']) ? $values + self::$view['vars'] : $values;
    }
    
    /*
     * 设置过滤器
     */
    public static function filter($name, $value)
    {
        self::$view['filters'][$name] = $value;
    }
    
    public static function filters(array $values)
    {
        self::$view['filters'] = isset(self::$view['filters']) ? $values + self::$view['filters'] : $values;
    }
    
    /*
     * 渲染页面
     */
    public static function render($tpl, array $vars = [])
    {
        ob_start();
        (static function($__file, $__vars) {
            extract($__vars, EXTR_SKIP);
            unset($__vars);
            return require $__file;
        })(self::make($tpl), isset(self::$view['vars']) ? $vars + self::$view['vars'] : $vars);
        return ob_get_clean();
    }
    
    /*
     * 检查更新视图文件，返回路径
     */
    public static function make($tpl, $force = false)
    {
        $phpfile = self::getView($tpl);
        if (!empty(self::$config['template']['engine'])) {
            if (!is_file($tplfile = self::getTemplateFile($tpl))) {
                throw new ViewException("Template file not found: $tplfile");
            }
            if ($force || self::$config['template']['debug'] || !is_file($phpfile)
                || filemtime($phpfile) < filemtime($tplfile)
            ) {
                self::complieTo(self::readTemplateFile($tplfile), $phpfile);
            }
        }
        return $phpfile;
    }
    
    /*
     * 检查视图文件是否存在
     */
    public static function exists($tpl)
    {
        return empty(self::$config['template']['engine']) ? is_php_file(self::getView($tpl))
														  : is_file(self::getTemplateFile($tpl));
    }
    
    /*
     * 错误页面，404 500页面等
     */
    public static function error($code, $message = null)
    {   
        if (isset(self::$config['error'][$code])) {
            return self::render(self::$config['error'][$code], compact('code', 'message'));
        }
        return $code == 404 ? ViewError::render404($message) : ViewError::renderError($message);
    }
    
    /*
     * 读取模版内容
     */
    public static function readTemplate($tpl)
    {
        if (is_file($file = self::getTemplateFile($tpl))) {
            return self::readTemplateFile($file);
        }
        throw new ViewException("Template file no exists: $file");
    }
    
    /*
     * 检查视图文件是否过期
     */
    public static function checkExpired($phpfile, ...$tpls)
    {
        if (empty(self::$config['template']['engine'])) {
            return;
        }
        foreach ($tpls as $tpl) {
            if (is_file($tplfile = self::getTemplateFile($tpl))) {
                throw new ViewException("Template file not found: $tplfile");
            }
            if (filemtime($phpfile) < filemtime($tplfile)) {
                $tpl = substr($phpfile, strlen(realpath(self::$config['dir'])) + 1, - strlen(self::$config['ext']));
                return self::complieTo(self::readTemplate($tpl), $phpfile);
            }
        }
    }
    
    /*
     * 调用过滤器
     */
    public static function callFilter($name, ...$params)
    {
        if (isset(self::$view['filters'][$name])) {
            return self::$view['filters'][$name](...$params);
        }
        throw new \BadMethodCallException("Call undefined filter: $name");
    }
    
    /*
     * 获取视图文件路径
     */
    private static function getView($tpl)
    {
        return self::$config['dir'].$tpl.self::$config['ext'];
    }
    
    /*
     * 获取模版文件路径
     */
    private static function getTemplateFile($tpl)
    {
        return (self::$config['template']['dir'] ?? self::$config['dir']).$tpl.self::$config['template']['ext'];
    }
    
    /*
     * 读取模版文件内容
     */
    private static function readTemplateFile($file)
    {
        if ($result = file_get_contents($file)) {
            return $result;
        }
        throw new ViewException("Template file read fail: $file");
    }

    /*
     * 编译模版并保存到视图文件
     */
    private static function complieTo($content, $file)
    {
        if (File::makeDir(dirname($file), 0777, true)) {
            $result = self::$config['template']['engine']::complie($content);
            if (file_put_contents($file, $result, LOCK_EX)) {
                if (OPCACHE_LOADED) {
                    opcache_compile_file($file);
                }
                return true;
            }
            throw new ViewException("View file write fail: $file");
        }
        throw new ViewException("View dir create fail: $dir");
    }
    
    /*
     * 清理
     */
    public static function clean()
    {
		self::$view = null;
    }
}
View::__init();
