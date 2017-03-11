<?php
namespace app\index\controller;

class Index
{
	//更新文件包
    public function index()
    { 
    	echo 'http://twothink.cn/update.zip';
    }
    //系统版本好
    public  function check_version(){
    	echo '1.1';
    }
}
