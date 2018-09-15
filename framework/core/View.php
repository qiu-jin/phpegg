<?php
namespace framework\core;

use framework\util\Arr;
use framework\core\Container;
use framework\core\http\Response;
use framework\core\exception\ViewException;
use framework\extend\view\Error as ViewError;

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
            if ($template = Arr::poll($config, 'template')) {
                self::$config['template'] = $template + self::$config['template'];
            }
            self::$config = $config + self::$config;
        }
        Event::on('exit', [__CLASS__, 'clean']);
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
        if (isset(self::$view['vars'])) {
            extract(self::$view['vars']);
        }
        if ($vars) {
            extract($vars);
        }
        ob_start();
        include self::make($tpl);
        return ob_get_clean();
    }
    
    /*
     * 检查更新视图文件，返回路径
     */
    public static function make($tpl, $force = false)
    {
        $phpfile = self::getView($tpl);
        if (self::$config['template']['engine']) {
            if (!is_file($tplfile = self::getTemplate($tpl))) {
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
        return isset(self::$config['template']['engine']) ? is_file(self::getTemplate($tpl)) 
                                                          : is_php_file(self::getView($tpl));
    }
    
    /*
     * 错误页面，404 500页面等
     */
    public static function error($code, $message = null)
    {   
        if (isset(self::$config['error'][$code])) {
            return self::render(self::$config['error'][$code], compact('code', 'message'));
        } elseif ($code == 404) {
            return ViewError::render404($message);
        } else {
            return ViewError::renderError($message);
        }
    }
    
    /*
     * 读取模版内容
     */
    public static function readTemplate($tpl)
    {
        if (is_file($file = self::getTemplate($tpl))) {
            return self::readTemplateFile($file);
        }
        throw new ViewException("Template file no exists: $file");
    }
    
    /*
     * 检查视图文件是否过期
     */
    public static function checkExpired($phpfile, ...$tpls)
    {
        if (!self::$config['template']['engine']) {
            return;
        }
        foreach ($tpls as $tpl) {
            if (is_file($tplfile = self::getTemplate($tpl))) {
                throw new ViewException("Template file not found: $tplfile");
            }
            if (filemtime($phpfile) < filemtime($tplfile)) {
                $s = strlen(realpath(self::$config['dir'])) + 1;
                $l = - strlen(self::$config['ext']);
                return self::complieTo(self::readTemplate(substr($phpfile, $s, $l)), $phpfile);
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
    private static function getTemplate($tpl)
    {
        return (self::$config['template']['dir'] ?? self::$config['dir']).$tpl.self::$config['template']['ext'];
    }
    
    /*
     * 读取模版文件内容
     */
    private static function readTemplateFile($file)
    {
        if ($res = file_get_contents($file)) {
            return $res;
        }
        throw new ViewException("Template file read fail: $file");
    }

    /*
     * 编译模版并保存到视图文件
     */
    private static function complieTo($content, $file)
    {
        if (is_dir($dir = dirname($file)) || mkdir($dir, 0777, true)) {
            $res = self::$config['template']['engine']::complie($content);
            if (file_put_contents($file, $res, LOCK_EX)) {
                if (OPCACHE_LOADED) {
                    opcache_compile_file($file);
                }
                return true;
            }
            throw new ViewException("View file write fail: $file");
        }
        throw new ViewException("View dir create fail: $dir");
    }
    
    public static function clean($name = null)
    {
        if ($name === null) {
            self::$view = null;
        } elseif (isset(self::$view[$name])) {
            self::$view[$name] = null;
        }
    }
}
View::__init();
