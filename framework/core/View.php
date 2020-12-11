<?php
namespace framework\core;

use framework\util\File;
use framework\core\http\Response;
use framework\core\misc\ViewError;
use framework\exception\ViewException;

class View
{    
    private static $init;
    // 数据
    private static $vars = [];
    // 过滤器
    private static $filters = [];
    // 配置
    private static $config = [
        // 视图文件扩展名
        'ext'       => '.php',
        // 视图文件目录
        'dir'       => APP_DIR.'view/',
        // 错误页面
        'error'     => null,
        // 模版配置（为空则不启用模版）
        'template'  => [
            // 模版文件扩展名
            'ext'	=> '.html',
            // 模版文件目录（为空则默认使用视图文件目录）
            'dir'	=> null,
            // 模版引擎类名
            'engine'	=> Template::class,
            // 是否强制编译模版
            'force_compile'	=> \app\env\APP_DEBUG,
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
    }
    
    /*
     * 设置变量
     */
    public static function var($name, $value)
    {
        self::$vars[$name] = $value;
    }
    
    public static function vars(array $values)
    {
        self::$vars = self::$vars ? $values + self::$vars : $values;
    }
    
    /*
     * 设置过滤器
     */
    public static function filter($name, $value)
    {
        self::$filters[$name] = $value;
    }
    
    public static function filters(array $values)
    {
        self::$filters = self::$filters ? $values + self::$filters : $values;
    }
	
    /*
     * 展示页面
     */
    public static function display($tpl, array $vars = null)
    {
		Response::html(self::render($tpl, $vars));
    }
    
    /*
     * 渲染页面
     */
    public static function render($tpl, array $vars = [], $clean = true)
    {
        ob_start();
		if (self::$vars) {
			$vars = $vars ? $vars + self::$vars : self::$vars;
		}
		(static function() {
			extract(func_get_arg(0), EXTR_SKIP);
			require func_get_arg(1);
		}) ($vars, self::path($tpl));
		if ($clean) {
			self::clean();
		}
        return ob_get_clean();
    }
    
    /*
     * 检查更新视图文件，返回路径
     */
    public static function path($tpl, $force_compile = false)
    {
        $vfile = self::getViewFilePath($tpl);
        if (self::$config['template']) {
            if (!is_file($tfile = self::getTemplateFilePath($tpl))) {
                throw new ViewException("Template file not found: $tfile");
            }
            if ($force_compile || self::$config['template']['force_compile'] || !is_file($vfile)
                || filemtime($vfile) < filemtime($tfile)
            ) {
                self::complieTo($vfile, file_get_contents($tfile));
            }
        }
        return $vfile;
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
		if (($content = File::get(self::getTemplateFilePath($tpl))) !== false) {
			return $content;
        }
        throw new ViewException("Template file not found: $file");
    }
    
    /*
     * 检查视图文件是否过期
     */
    public static function checkExpired($vfile, ...$tpls)
    {
        if (self::$config['template']) {
	        foreach ($tpls as $tpl) {
	            if (!is_file($tfile = self::getTemplatePath($tpl))) {
	                throw new ViewException("模版文件: $tfile 不存在");
	            }
	            if (filemtime($vfile) < filemtime($tfile)) {
					$dir = realpath(self::$config['dir']);
					$len = strlen($dir);
					if (strncmp($vfile, $dir, $len) === 0) {
		                $t = substr($vfile, $len + 1, - strlen(self::$config['ext']));
		                self::complieTo($vfile, self::readTemplate($t));
						return true;
					}
					throw new ViewException("视图文件: $vfile 与视图目录: $dir 不符");
	            }
	        }
        }
    }
    
    /*
     * 调用过滤器
     */
    public static function callFilter($name, ...$params)
    {
        if (isset(self::$filters[$name])) {
            return self::$filters[$name](...$params);
        }
        throw new \BadMethodCallException("调用未定义过滤器: $name");
    }
	
    /*
     * 检查视图文件是否存在
     */
    public static function exists($tpl)
    {
        return self::$config['template'] ? 
			is_file(self::getTemplateFilePath($tpl)) : is_php_file(self::getViewFilePath($tpl));
    }
	
    /*
     * 获取视图文件路径
     */
    private static function getViewFilePath($tpl)
    {
        return self::$config['dir'].$tpl.self::$config['ext'];
    }
    
    /*
     * 获取模版文件路径
     */
    private static function getTemplateFilePath($tpl)
    {
        return (self::$config['template']['dir'] ?? self::$config['dir']).$tpl.self::$config['template']['ext'];
    }

    /*
     * 编译模版并保存到视图文件
     */
    private static function complieTo($file, $content)
    {
        if (File::put($file, self::$config['template']['engine']::complie($content))) {
			return OPCACHE_LOADED && opcache_compile_file($file);
        }
        throw new ViewException("写入视图文件: $file 失败");
    }
    
    /*
     * 清理
     */
    public static function clean()
    {
		self::$vars = self::$filters = [];
    }
}
View::__init();
