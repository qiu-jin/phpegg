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
        'ext'       => '.php',
        'dir'       => APP_DIR.'view/',
        'template'  => [
            'ext'       => '.html',
            //'dir'       => APP_DIR.'view/',
            'debug'     => APP_DEBUG,
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
            $template = Arr::pull($config, 'template');
            if ($template === false) {
                self::$config['template'] = false;
            } elseif (is_array($template)) {
                self::$config['template'] = $template + self::$config['template'];
            }
            self::$config = $config + self::$config;
        }
        Event::on('exit', __CLASS__.'::clean');
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
        self::$view['vars'] = $values + (self::$view['vars'] ?? []);
    }
    
    /*
     * 设置过滤器
     */
    public static function filter($name, callable $value)
    {
        self::$view['filters'][$name] = $value;
    }
    
    public static function filters(array $values)
    {
        self::$view['filters'] = $values + (self::$view['filters'] ?? []);
    }
    
    /*
     * 渲染页面
     */
    public static function render($tpl, array $vars = [])
    {
        if (isset(self::$view['vars'])) {
            extract(self::$view['vars'], EXTR_SKIP);
        }
        if ($vars) {
            extract($vars, EXTR_SKIP);
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
        if (self::$config['template']) {
            if (!is_file($tplfile = self::getTemplate($tpl))) {
                throw new ViewException("Template file not found: $tplfile");
            }
            if ($force
                || self::$config['template']['debug']
                || !is_file($phpfile)
                || filemtime($phpfile) < filemtime($tplfile)
            ) {
                self::complieTo(self::readTemplateFile($file), $phpfile);
            }
        }
        return $phpfile;
    }
    
    /*
     * 检查视图文件是否存在
     */
    public static function exists($tpl)
    {
        return self::$config['template'] ? is_file(self::getTemplate($tpl)) : is_php_file(self::getView($tpl));
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
     * 视图魔术方法
     */
    public static function __callStatic($method, $params = [])
    {
        if (isset(self::$config['methods'][$method])) {
            $tpl = array_pop($vars = self::$config['methods'][$method]);
            if ($vars) {
                foreach (array_keys($vars) as $i => $v) {
                    if (!isset($params[$i])) {
                        break;
                    }
                    $vars[$v] = $params[$i];
                }
            }
            return Response::view($tpl, $vars);
        }
        throw new \BadMethodCallException('Call to undefined method '.__CLASS__."::$method");
    }

    /*
     *  模版编译宏
     */
    private static function filterMacro($name, $args)
    {
        return __CLASS__."::callFilter('$name'".($args ? ', '.implode(', ', $args) : '').")";
    }
    
    private static function ContainerMacro($name)
    {
        return Container::class."::make('$name')";
    }
    
    private static function includeMacro($name, $is_var = false)
    {
        return 'include '.__CLASS__.'::make('.($is_var ? $name : "'$name'").');';
    }
    
    private static function checkExpiredMacro($names)
    {
        return 'if ('.__CLASS__."::checkExpired(__FILE__, '".implode("', '", $names)."')) return include __FILE__;";
    }
    
    /*
     * 读取模版内容
     */
    private static function readTemplate($tpl)
    {
        if (is_file($file = self::getTemplate($tpl))) {
            return self::readTemplateFile($file);
        }
        throw new ViewException("Template file no exists: $file");
    }
    
    /*
     * 检查视图文件是否过期
     */
    private static function checkExpired($phpfile, ...$tpls)
    {
        if (!self::$config['template']) {
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
    private static function callFilter($name, ...$params)
    {
        if (isset(self::$view['filters'][$name])) {
            return self::$view['filters'][$name](...$params);
        }
        throw new \BadMethodCallException('Call to undefined filter '.$name);
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
        if (empty(self::$config['template']['dir'])) {
            return self::$config['dir'].$tpl.self::$config['template']['ext'];
        } else {
            return self::$config['template']['dir'].$tpl.self::$config['template']['ext'];
        }
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
