<?php
 
namespace app\common\validate;
use think\Validate;
/**
*  模型属性验证
*/
class DocumentArticle extends Validate{
    // 验证规则
    protected $rule = [
        ['content', 'getContent', '内容不能为空！']
    ];  
    
    protected $scene = array(
        'update'     => 'content',//写入时验证  
    );  
    /**
     * 获取文章的详细内容
     * @return boolean
     * @author 艺品网络  <twothink.cn>
     */
    protected function getContent($value,$rlue='',$data){
    	$type = $data['type'];
    	$content = $data['content'];
    	if($type > 1){//主题和段落必须有内容
    		if(empty($content)){
    			return false;
    		}
    	}else{  //目录没内容则生成空字符串
    		if(empty($content)){
    			$data['content'] = ' ';
    		}
    	}
    	return true;
    } 

}