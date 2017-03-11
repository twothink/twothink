<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络 <http://www.twothink.cn>
// +----------------------------------------------------------------------

namespace app\home\controller;
use app\user\api\UserApi;

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class User extends Home {

	/* 用户中心首页 */
	public function index(){
		
	}

	/* 注册页面 */
	public function register($username = '', $password = '', $repassword = '', $email = '', $verify = ''){
        if(!config('user_allow_register')){
            $this->error('注册已关闭');
        }
		if($this->request->isPost()){ //注册用户
			/* 检测验证码 */
		   if(!captcha_check($verify)){
                $this->error('验证码输入错误！');
            }

			/* 检测密码 */
			if($password != $repassword){
				$this->error('密码和重复密码不一致！');
			}			

			/* 调用注册接口注册用户 */
            $User = new UserApi;
			$uid = $User->register($username, $password, $email); 
			if(0 < $uid){ //注册成功
				//TODO: 发送验证邮件
				$this->success('注册成功！',url('login'));
			} else { //注册失败，显示错误信息
				$this->error($uid);
			}

		} else { //显示注册表单
			return $this->fetch();
		}
	}

	/* 登录页面 */
	public function login($username = '', $password = '', $verify = ''){
		if($this->request->isPost()){ //登录验证
			/* 检测验证码 */
		    if(!captcha_check($verify)){
                $this->error('验证码输入错误！');
            }

			/* 调用UC登录接口登录 */
			$user = new UserApi;
			$uid = $user->login($username, $password);
			 
			if(0 < $uid){ //UC登录成功
				/* 登录用户 */
				$Member = model('Member');
				if($Member->login($uid)){ //登录用户
					//TODO:跳转到登录前页面
					$this->success('登录成功！',url('Home/Index/index'));
				} else {
					$this->error($Member->getError());
				}

			} else { //登录失败
				switch($uid) {
					case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
					case -2: $error = '密码错误！'; break;
					default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
				}
				$this->error($error);
			}

		} else { //显示登录表单
			return $this->fetch();
		}
	}

	/* 退出登录 */
	public function logout(){
		if(is_login()){
			model('Member')->logout();
			$this->success('退出成功！', url('User/login'));
		} else {
			$this->redirect('User/login');
		}
	}
 


    /**
     * 修改密码提交
     * @author 艺品网络  <twothink.cn>
     */
    public function profile(){
		if ( !is_login() ) {
			$this->error( '您还没有登陆',url('User/login') );
		}
        if ($this->request->isPost()) {
            //获取参数
            $uid        =   is_login();
            $data = input('param.'); 
            $password   =  $data['old'];;
            $repassword = $data['repassword'];
            $data['password'] = $data['password'];
            empty($password) && $this->error('请输入原密码');
            empty($data['password']) && $this->error('请输入新密码');
            empty($repassword) && $this->error('请输入确认密码');

            if($data['password'] !== $repassword){
                $this->error('您输入的新密码与确认密码不一致');
            }

            $Api = new UserApi();
            $res = $Api->updateInfo($uid, $password, $data);
            if($res['status']){
                $this->success('修改密码成功！');
            }else{
                $this->error($res['info']);
            }
        }else{
            return $this->fetch();
        }
    }

}
