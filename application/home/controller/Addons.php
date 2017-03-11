<?php

namespace app\home\controller;
use think\Controller;
use think\Request;
use think\Config;
use think\Loader;


/**
 * 插件执行默认控制器
 * Class Addons
 * @package think\addons
 */
class Addons extends Controller
{
	public function _initialize(){
		/* 读取数据库中的配置 */
		$config = cache('db_config_data');
		if(!$config){
			$config = api('Config/lists');
			cache('db_config_data',$config);
		}
		config($config); //添加配置
	}
    /**
     * 插件执行
     */
    public function execute($_addons = null, $_controller = null, $_action = null)
    {
        if (!empty($_addons) && !empty($_controller) && !empty($_action)) {
            // 获取类的命名空间
            $class = get_addon_class($_addons, 'controller', $_controller);

            if(class_exists($class)) {
                $model = new $class();
                if ($model === false) {
                    $this->error(lang('addon init fail'));
                }
                // 调用操作
                return  \think\App::invokeMethod([$class, $_action]);
            }else{
                $this->error(lang('控制器不存在'.$class));
            }
        }
        $this->error(lang('没有指定插件名称，控制器或操作！'));
    }

    // 当前插件操作
    protected $addon = null;
    protected $controller = null;
    protected $action = null;
    // 当前template
    protected $template;
    // 模板配置信息
    protected $config = [
    		'type' => 'Think',
    		'view_path' => '',
    		'view_suffix' => 'html',
    		'strip_space' => true,
    		'view_depr' => DS,
    		'tpl_begin' => '{',
    		'tpl_end' => '}',
    		'taglib_begin' => '{',
    		'taglib_end' => '}',
    ];

    /**
     * 架构函数
     * @param Request $request Request对象
     * @access public
    */
    public function __construct(Request $request = null)
    {
    	// 生成request对象
    	$this->request = is_null($request) ? Request::instance() : $request;
    	// 初始化配置信息
    	$this->config = Config::get('template') ?: $this->config;
    	// 处理路由参数
    	$route = $this->request->param();
    	// 格式化路由的插件位置
    	$this->action = $route['_action'];
    	$this->controller = $route['_controller'];
    	$this->addon = $route['_addons'];
    	// 生成view_path
    	$view_path = $this->config['view_path'] ?: 'view';
    	// 重置配置
    	Config::set('template.view_path', TWOTHINK_ADDON_PATH . $this->addon . DS . $view_path . DS);

    	parent::__construct($request);
    }

    /**
     * 加载模板输出
     * @access protected
     * @param string $template 模板文件名
     * @param array $vars 模板输出变量
     * @param array $replace 模板替换
     * @param array $config 模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
    	$controller = Loader::parseName($this->controller);
    	if ('think' == strtolower($this->config['type']) && $controller && 0 !== strpos($template, '/')) {
    		$depr = $this->config['view_depr'];
    		$template = str_replace(['/', ':'], $depr, $template);
    		if ('' == $template) {
    			// 如果模板文件名为空 按照默认规则定位
    			$template = str_replace('.', DS, $controller) . $depr . $this->action;
    		} elseif (false === strpos($template, $depr)) {
    			$template = str_replace('.', DS, $controller) . $depr . $template;
    		}
    	}
    	return parent::fetch($template, $vars, $replace, $config);
    }
}
