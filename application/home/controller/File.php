<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace app\home\controller;
use app\home\controller\Home;
use app\home\logic\DocumentDownload;
/**
 * 文件控制器
 * 主要用于下载模型的文件上传和下载
 */

class File extends Home {
	/* 文件上传 */
	/** 实际上这个方法没用上，客户端的一个控件完成了上传： 2017.1.10 ,xdeepbreath@qq.com*/
	public function upload(){
		$return  = array('status' => 1, 'info' => '上传成功', 'data' => '');
		/* 调用文件上传组件上传文件 */
		$info = model('File')->upload();

		/* 记录附件信息 */
		if($info){
			$return['data'] = think_encrypt(json_encode($info['download']));
		} else {
			$return['status'] = 0;
			$return['info']   = $File->getError();
		}

		/* 返回JSON数据 */
		return json($return);
	}

	/* 下载文件 */
	public function download($id = null){
		if(empty($id) || !is_numeric($id)){
			$this->error('参数错误！');
		}
		$logic = model('DocumentDownload','logic');
		if(!$logic->download($id)){
			$this->error($logic->getError());
		}

	}
}
