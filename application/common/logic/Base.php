<?php 
namespace app\common\logic;
use think\Model;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class Base extends Model { 
	protected $autoWriteTimestamp = false;  
	protected $name;
	
	public function __construct($name=''){
		parent::__construct();
		if(!empty($name)){ 
			$this->name=$name;
		} 
	}   
	

    /**
     * 获取模型详细信息
     * @param  integer $id 文档ID
     * @return array       当前模型详细信息
     */
    public function detail($id) {    
    	//查询表字段 
    	$fields = \think\Db::connect()->getTableFields(array('name'=>$this->name));  
        if ($fields == false) {
            $data = array();
        } else { 
            $data = \think\Db::name($this->name)->field(true)->where(['id'=>$id])->find();  
            if (!$data) {
                $this->error = '获取详细信息出错！';
                return false;
            }
        }   
        return $data;
    }

    /**
     * 新增或添加模型数据
     * @param  number $id 文章ID 
     * @return boolean    true-操作成功，false-操作失败
     */
    public function updates($id = 0) { 
        /* 获取数据 */
        $data = input();   
        if (empty($data['id'])) {//新增数据 
            $data['id'] = $id;   
            $id = $this->data($data)->allowField(true)->save();  
            if (!$id) {
                $this->error = '新增数据失败！';
                return false;
            }
        } else { //更新数据  
        	$id = $data['id'];    
            $status = $this->data($data,true)->allowField(true)->save($data,['id'=>$id]); 
            if (false === $status) {
                $this->error = '更新数据失败！';
                return false;
            }
        } 
        return true;
    }

    /**
     * 模型数据自动保存
     * @return boolean
     */
    public function autoSave($id = 0) {
        $this->validate = array();
        return $this->updates($id);
    }

    /**
     * 检测属性的自动验证和自动完成属性(自动完成需手动建立模型) 并进行验证
     * 验证场景  insert和update二个个场景，可以分别在新增和编辑
     * @return boolean
     */
    public function checkModelAttr($model_id,$data){ 
        $fields     =   get_model_attribute($model_id,false);  
         
        $validate   =   array(); 
        foreach($fields as $key=>$attr){
        	switch ($attr['validate_time']) {
        		case '1':
        			if (empty($data['id'])) {//新增数据   
			        	// 自动验证规则
			            if(!empty($attr['validate_rule'])) {
			            	if($attr['is_must']){// 必填字段
			            		$require = 'require|';
			            		$require_msg= $attr['title'].'不能为空|';
			            	}
			            	 $msg = $attr['error_info']?$attr['error_info']:$attr['title'].'验证错误';
			            	 $validate[]=[$attr['name'], $require.$attr['validate_rule'],$require_msg.$msg];
			              
			            }elseif($attr['is_must']){
			            	$validate[]=[$attr['name'], 'require', $attr['title'].'不能为空'];
			            }
        			}
        			break;
        		case '2':
        			if (!empty($data['id'])) {//编辑 
        				// 自动验证规则
        				if(!empty($attr['validate_rule'])) {
        					if($attr['is_must']){// 必填字段
        						$require = 'require|';
        						$require_msg= $attr['title'].'不能为空|';
        					}
        					$msg = $attr['error_info']?$attr['error_info']:$attr['title'].'验证错误';
        					$validate[]=[$attr['name'], $require.$attr['validate_rule'],$require_msg.$msg];
        					 
        				}elseif($attr['is_must']){
        					$validate[]=[$attr['name'], 'require', $attr['title'].'不能为空'];
        				}
        			}
        			break;
        		default: 
        			// 自动验证规则
        			if(!empty($attr['validate_rule'])) {
        				if($attr['is_must']){// 必填字段
        					$require = 'require|';
        					$require_msg= $attr['title'].'不能为空|';
        				}
        				$msg = $attr['error_info']?$attr['error_info']:$attr['title'].'验证错误';
        				$validate[]=[$attr['name'], $require.$attr['validate_rule'],$require_msg.$msg];
        				 
        			}elseif($attr['is_must']){
        				$validate[]=[$attr['name'], 'require', $attr['title'].'不能为空'];
        			}
        			break;
        	} 
	         
           
            // 自动完成规则
//             if(!empty($attr['auto_rule'])) {
//                 $auto[]  =  array($attr['name'],$attr['auto_rule'],$attr['auto_time'],$attr['auto_type']);
//             }elseif('checkbox'==$attr['type']){ // 多选型
//                 $auto[] =   array($attr['name'],'arr2str',3,'function');
//             }elseif('datetime' == $attr['type'] || 'date' == $attr['type']){ // 日期型
//                 $auto[] =   array($attr['name'],'strtotime',3,'function');
//             }
        }   
        
        //判断验证模型
        $validate_status = true;
        $module = \think\Request::instance()->module();
        $class = \think\Loader::parseClass($module, 'validate', $this->name, config('class_suffix'));
        if (!class_exists($class)) {//判断app\{$model}\logic\是否存在模型 
        	$common = 'common';
			$class = str_replace('\\' . $module . '\\', '\\' . $common . '\\', $class);
			if (!class_exists($class)) {
				$validate_status = false;
			}
        }  
        if($validate_status){//添加验证规则
        	$validate_module = \think\Loader::validate($this->name);
        	//验证场景
        	$scene = 'update';
        	 if(empty($data['id'])) { 
        			$scene = 'insert'; 
        	}
        	$validate_module->scene($scene);
        	$validate_module->rule($validate);  
        }else{
        	$validate_module = \think\Validate::make($validate);
        }    
        if (!$validate_module->check($data)) { 
        	$this->error = $validate_module->getError();  
        	return false;
        }    
        return true;
    }
}
