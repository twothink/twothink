<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace app\home\model;
use think\Model;

/**
 * 文件模型
 * 负责文件的下载和上传
 */

class File extends Model{
	/**
	 * 文件模型自动完成
	 * @var array
	 */
	// protected $_auto = array(
	// 	array('create_time', NOW_TIME, self::MODEL_INSERT),
	// );

	/**
	 * 文件模型字段映射
	 * @var array
	 */
	// protected $_map = array(
	// 	'type' => 'mime',
	// );

	/**
	 * 文件上传 : 实际上这个TP5的方法没用上，是客户端的组件实际执行了上传: 2017.1.10 ,xdeepbreath@qq.com
	 */
	public function upload($rootpath){
		// /* 上传文件 */

		/* 检测文件是否存在 */
		$isData=$this->isFile(array('md5'=>$files->hash('md5'),'sha1'=>$files->hash()));
		if($isData){
			return $isData; //文件上传成功
		}
		// 上传文件验证
		$info = $files->validate([
				'ext' => $setting['ext'],
				'size' => $setting['size']
		]
		)->rule($setting['saveName'])->move($setting['rootPath'],true,$setting['replace']);


		if($info){
			/* 记录文件信息 */
			$value['name']  = $info->getInfo('name');
			$value['savename']  = $info->getBasename();
			$value['savepath']  = basename($info->getPath()).'/';
			$value['ext']      = $info->getExtension();
			$value['mime']   = $info->getInfo('type');
			$value['size'] = $info->getInfo('size');
			$value['md5']  = $files->hash('md5');
			$value['sha1']  = $files->hash('sha1');
			$value['location']  = 0;
			$value['create_time']  = time();
			if($add=$this->create($value)){
				$value['id'] = $add->id;
			}
			return $value; //文件上传成功
		} else {
			$this->error = $files->getError();
			return false;
		}
	}

	/**
	 * 下载指定文件
	 * @param  number  $root 文件存储根目录
	 * @param  integer $id   文件ID
	 * @param  string   $args     回调函数参数
	 * @return boolean       false-下载失败，否则输出下载文件
	 */
	public function download($root, $id, $callback = null, $args = null){
		      //  $File->download($root, $info->name['file_id'], $call, $info->name['id'])
		/* 获取下载文件信息 */
		$file = $this->find($id);
		if(!$file){
			$this->error = '不存在该文件！';
			return false;
		}

		/* 下载文件 */
		switch ($file['location']) {
			case 0: //下载本地文件
				$file['rootpath'] = $root;
				return $this->downLocalFile($file, $callback, $args);
			case 1: //TODO: 下载远程FTP文件
				break;
			default:
				$this->error = '不支持的文件存储类型！';
				return false;

		}

	}

	/**
	 * 检测当前上传的文件是否已经存在
	 * @param  array   $file 文件上传数组
	 * @return boolean       文件信息， false - 不存在该文件
	 */
	public function isFile($file){
		if(empty($file['md5'])){
			throw new Exception('缺少参数:md5');
		}
		/* 查找文件 */
		$map = array('md5' => $file['md5']);
		return $this->field(true)->where($map)->find();
	}

	/**
	 * 下载本地文件
	 * @param  array    $file     文件信息数组
	 * @param  callable $callback 下载回调函数，一般用于增加下载次数
	 * @param  string   $args     回调函数参数
	 * @return boolean            下载失败返回false
	 */
	private function downLocalFile($file, $callback = null, $args = null){

		$fullpath= $file['rootpath'].$file['savepath'].$file['savename'];

		if(is_file($fullpath)){
			/* 调用回调函数新增下载数 */
			is_callable($callback) && call_user_func($callback, $args);

			/* 执行下载 */ //TODO: 大文件断点续传
			header("Content-Description: File Transfer");
			header('Content-type: ' . $file['mime']);
			header('Content-Length:' . $file['size']);
			if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { //for IE
				header('Content-Disposition: attachment; filename="' . rawurlencode($file['name']) . '"');
			} else {
				header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
			}
			readfile($fullpath);
			exit;
		} else {
			$this->error = '文件已被删除！';
			return false;
		}
	}

}
