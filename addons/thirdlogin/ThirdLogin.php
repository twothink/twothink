<?php

namespace addons\thirdlogin;
use common\controller\Addon;

/**
 * 快捷登录插件插件
 * @author iszhang
 */

    class ThirdLogin extends Addon{

        public $info = array(
            'name'=>'ThirdLogin',
            'title'=>'快捷登录插件',
            'description'=>'目前登录平台为：腾讯QQ，其它请自行添加',
            'status'=>1,
            'author'=>'iszhang',
            'version'=>'0.1'
        );

        public function install(){
        	$sql_file = config('addons').'thirdlogin/install.sql';
        	// 执行sql文件
        	$res = $this->executeSqlFile($sql_file);
        	// 错误处理
        	if(!empty($res)) {
        		// 清除已导入的数据
        		$this->uninstall();
        	}   
        	//加载钩子 
            $model = M('hooks');
	        if ($model->where("name='ThirdLogin' AND type=2")->find()) {
	            return true;
	        }
	        $model->name = 'ThirdLogin';
	        $model->description = '第三方快捷登录钩子';
	        $model->update_time = time();
	        $model->type = 1;
	        if ($model->add()){
	            return true;
	        }else {
	            return false;
	        }
        }

        public function uninstall(){
        	// 数据库表前缀
        	$db_prefix = config('database.prefix');
        	$sql = array("DROP TABLE IF EXISTS `{$db_prefix}test`;",);
        	// 执行SQL
        	foreach($sql as $v) {
        		//M('')->execute($v); 暂不卸载数据库
        	}
            
        	$model = model('hooks');
	        if ($model->where("name='ThirdLogin'")->delete()) {
	            return true;
	        }else {
	            return false;
	        }
        }
        
        //实现的Login钩子方法
        public function ThirdLogin($param){
            $config = $this->getConfig();
            $this->assign('third_login',$config['login_plugin']);
            $this->display('oauth');
        }        

        //实现的pageHeader钩子方法
        public function pageHeader($param){
			$config = $this->getConfig();
			echo $config['platformMeta'];
        }

        /**
         * 执行SQL文件
         * @access public
         * @param string  $file 要执行的sql文件路径
         * @param boolean $stop 遇错是否停止  默认为true
         * @param string  $db_charset 数据库编码 默认为utf-8
         * @return array
         */
        public function executeSqlFile($file,$stop = true,$db_charset = 'utf-8') {
            if (!is_readable($file)) {
                $error = array(
                    'error_code' => 'SQL文件不可读',
                    'error_sql'  => '',
                 );
                return $error;
            }

            $fp = fopen($file, 'rb');
            $sql = fread($fp, filesize($file));
            fclose($fp);

            $sql = str_replace("\r", "\n", str_replace('`'.'ts_', '`'.config('database.prefix'), $sql));

            foreach (explode(";\n", trim($sql)) as $query) {
                $query = trim($query);
                if($query) {
                    if(substr($query, 0, 12) == 'CREATE TABLE') {
                        //预处理建表语句
                        $db_charset = (strpos($db_charset, '-') === FALSE) ? $db_charset : str_replace('-', '', $db_charset);
                        $type   = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $query));
                        $type   = in_array($type, array("MYISAM", "HEAP")) ? $type : "MYISAM";
                        $_temp_query = preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $query).
                            (mysql_get_server_info() > "4.1" ? " ENGINE=$type DEFAULT CHARSET=$db_charset" : " TYPE=$type");

                        $res = \think\Db::execute($_temp_query);
                    }else {
                        $res = \think\Db::execute($query);
                    }
                    if($res === false) {
                        $error[] = array(
                            'error_code' => \think\Db::getDbError(),
                            'error_sql'  => $query,
                           );

                        if($stop) return $error[0];
                    }
                }
            }
            return $error;
        }        
    }