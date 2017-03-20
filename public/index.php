<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 定义__ROOT__
if (!defined('__ROOT__')) {
	$_root = rtrim(dirname(rtrim($_SERVER['SCRIPT_NAME'], '/')), '/');
	define('__ROOT__', (('/' == $_root || '\\' == $_root) ? '' : $_root));
}
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
define('NOW_TIME',      $_SERVER['REQUEST_TIME']);
//是否安装
if(!is_file(APP_PATH . '/database.php')){
    $module_name = \think\Request::instance()->module();
	header('Location: ./install.php/install/index/'.$module_name);exit();
}
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
