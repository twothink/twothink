<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络
// +----------------------------------------------------------------------

namespace app\common\logic;
use think\Model;

/**
 * 文档基础模型逻辑层公共模型
 * 所有继承模型都需要继承此模型
 */
class Documentbase extends Model{
	protected $autoWriteTimestamp = false;
	protected $name;

	public function __construct($name=''){
		parent::__construct();
		if(!empty($name)){
			$this->name=$name;
		}
	}
    /**
     * 获取详情页数据
     * @param  integer $id 文档ID
     * @return array       详细数据
     */
    public function detail($id){
        /* 获取基础数据 */
    	$info = \think\Db::name($this->name)->field(true)->find($id);
        if(!(is_array($info) || 1 !== $info['status'])){
            $this->error = '文档被禁用或已删除！';
            return false;
        }
        //判断model_id不存在不往下执行
        if(!$info['model_id']){
        	return $info;
        }
        /* 获取模型数据 */
        $logic  = logic($info['model_id']);
        $detail = $logic->detail($id); //获取指定ID的数据
        if(!$detail){
            $this->error = $logic->getError();
            return false;
        }
        $info = array_merge($info, $detail);
        return $info;
    }

    /**
     * 新增或更新一个文档
     * @param array  $data 手动传入的数据
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     */
    public function updates($data = null){
        /* 检查文档类型是否符合要求 */
        $res = $this->checkDocumentType( input('type',2), input('pid') );
        if(!$res['status']){
            $this->error = $res['info'];
            return false;
        }
        if(empty($data)){
	        /* 获取数据对象 */
			$data = \think\Request::instance()->post();
        }
		//验证基础模型
        if(!$this->checkModelAttr($data['model_id'],$data)){
        	$this->error = $this->getError();
        	return false;
        }
        /* 添加或新增基础内容 */
        if(empty($data['id'])){ //新增数据
        	unset($data['id']);
            $id = $this->data($data)->allowField(true)->save(); //添加基础内容
            if(!$id){
                $this->error = $this->getError();
                return false;
            }
            $id = $this->id;
        } else { //更新数据
        	$id = $data['id'];
            $status = $this->allowField(true)->isUpdate(true)->save($data); //更新基础内容
            if(false === $status){
                $this->error = '更新基础内容出错！';
                return false;
            }
            $id = $data['id'];
        }
        /*获取模型信息*/
        if(!get_document_model($data['model_id'],'extend')){
        	return $data;
        }
        /* 添加或新增扩展内容 */
        $logic = logic($data['model_id']);

         if(!$logic->checkModelAttr($data['model_id'],$data)){
        	$this->error = $logic->getError();
        	return false;
        }
        if(!$logic->updates($id,false)){
            if(isset($id)){ //新增失败，删除基础数据
                $this->delete($id);
            }
            $this->error = $logic->getError();
            return false;
        }
//         hook('documentSaveComplete', array('model_id'=>$data['model_id']));

        //行为记录
        if($id){
            action_log('add_'.$this->name, $this->name, $id, UID);
        }

        //内容添加或更新完成
        return $data;
    }

    /**
     * 获取数据状态
     * @return integer 数据状态
     */
    protected function getStatus(){
        $id = input('post.id');
        if(empty($id)){	//新增
        	$cate = input('post.category_id');
        	$check 	=	db('Category')->getFieldById($cate,'check');
            $status = 	$check ? 2 : 1;
        }else{				//更新
            $status = db($this->name)->getFieldById($id, 'status');
            //编辑草稿改变状态
            if($status == 3){
                $status = 1;
            }
        }
        return $status;
    }

    /**
     * 获取根节点id
     * @return integer 数据id
     * @author 艺品网络  <twothink.cn>
     */
    protected function getRoot(){
        $pid = input('post.pid');
        if($pid == 0){
            return 0;
        }
        $p_root = $this->getFieldById($pid, 'root');
        return $p_root == 0 ? $pid : $p_root;
    }

    /**
     * 创建时间不写则取当前时间
     * @return int 时间戳
     * @author 艺品网络  <twothink.cn>
     */
    protected function getCreateTime(){
        $create_time    =   input('post.create_time');
        return $create_time?strtotime($create_time):NOW_TIME;
    }

    /**
     * 生成不重复的name标识
     * @author 艺品网络  <twothink.cn>
     */
    private function generateName(){
        $str = 'abcdefghijklmnopqrstuvwxyz0123456789';	//源字符串
        $min = 10;
        $max = 39;
        $name = false;
        while (true){
            $length = rand($min, $max);	//生成的标识长度
            $name = substr(str_shuffle(substr($str,0,26)), 0, 1);	//第一个字母
            $name .= substr(str_shuffle($str), 0, $length);
            //检查是否已存在
            $res = $this->getFieldByName($name, 'id');
            if(!$res){
                break;
            }
        }
        return $name;
    }

    /**
     * 生成推荐位的值
     * @return number 推荐位
     * @author 艺品网络  <twothink.cn>
     */
    protected function getPosition($position){
        if(!is_array($position)){
            return 0;
        }else{
            $pos = 0;
            foreach ($position as $key=>$value){
                $pos += $value;		//将各个推荐位的值相加
            }
            return $pos;
        }
    }


    /**
     * 删除状态为-1的数据（包含扩展模型）
     * @return true 删除成功， false 删除失败
     * @author 艺品网络  <twothink.cn>
     */
    public function remove(){
        //查询假删除的基础数据
        if ( is_administrator() ) {
            $map = array('status'=>-1);
        }else{
            $cate_ids = AuthGroupModel::getAuthCategories(UID);
            $map = array('status'=>-1,'category_id'=>array( 'IN',trim(implode(',',$cate_ids),',') ));
        }
        $base_list = \think\Db::name($this->name)->where($map)->field('id,model_id')->select();
        //删除扩展模型数据
        $base_ids = array_column($base_list,'id');
        //孤儿数据
        $orphan   = get_stemma( $base_ids,$this, 'id,model_id');
        $all_list  = array_merge( $base_list,$orphan );
        foreach ($all_list as $key=>$value){
        	$model_name =get_document_model($value['model_id'],'name');
            \think\Db::name($this->name.'_'.$model_name)->delete($value['id']);
        }
        //删除基础数据
        $ids = array_merge( $base_ids, (array)array_column($orphan,'id') );
        if(!empty($ids)){
            $res = $this->where( array( 'id'=>array( 'IN',trim(implode(',',$ids),',') ) ) )->delete();
        }
        return $res;
    }

    /**
     * 获取链接id
     * @return int 链接对应的id
     * @author 艺品网络  <twothink.cn>
     */
    protected function getLink(){
        $link = input('post.link_id');
        if(empty($link)){
            return 0;
        } else if(is_numeric($link)){
            return $link;
        }
        $res = model('Url')->update(array('url'=>$link));
        return $res['id'];
    }

    /**
     * 保存为草稿
     * @return array 完整的数据， false 保存出错
     * @author 艺品网络  <twothink.cn>
     */
    public function autoSave(){
        $post = input('post.');

        /* 检查文档类型是否符合要求 */
        $res = $this->checkDocumentType( input('type',2), input('pid') );
        if(!$res['status']){
            $this->error = $res['info'];
            return false;
        }

        //触发自动保存的字段
        $save_list = array('name','title','description','position','link_id','cover_id','deadline','create_time','content');
        foreach ($save_list as $value){
            if(!empty($post[$value])){
                $if_save = true;
                break;
            }
        }

        if(!$if_save){
            $this->error = '您未填写任何内容';
            return false;
        }

        //重置自动验证
        $this->_validate = array(
            array('name', '/^[a-zA-Z]\w{0,39}$/', '文档标识不合法', self::VALUE_VALIDATE, 'regex', self::MODEL_BOTH),
            array('name', '', '标识已经存在', self::VALUE_VALIDATE, 'unique', self::MODEL_BOTH),
            array('title', '1,80', '标题长度不能超过80个字符', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
            array('description', '1,140', '简介长度不能超过140个字符', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
            array('category_id', 'require', '分类不能为空', self::MUST_VALIDATE , 'regex', self::MODEL_BOTH),
            array('category_id', 'check_category', '该分类不允许发布内容', self::EXISTS_VALIDATE , 'function', self::MODEL_UPDATE),
            array('category_id,type', 'check_category', '内容类型不正确', self::MUST_VALIDATE, 'function', self::MODEL_INSERT),
            array('model_id,pid,category_id', 'check_catgory_model', '该分类没有绑定当前模型', self::MUST_VALIDATE , 'function', self::MODEL_INSERT),
            array('deadline', '/^\d{4,4}-\d{1,2}-\d{1,2}(\s\d{1,2}:\d{1,2}(:\d{1,2})?)?$/', '日期格式不合法,请使用"年-月-日 时:分"格式,全部为数字', self::VALUE_VALIDATE  , 'regex', self::MODEL_BOTH),
            array('create_time', '/^\d{4,4}-\d{1,2}-\d{1,2}(\s\d{1,2}:\d{1,2}(:\d{1,2})?)?$/', '日期格式不合法,请使用"年-月-日 时:分"格式,全部为数字', self::VALUE_VALIDATE  , 'regex', self::MODEL_BOTH),
        );
        $this->_auto[] = array('status', '3', self::MODEL_BOTH);

        if(!($data = $this->create())){
            return false;
        }

        /* 添加或新增基础内容 */
        if(empty($data['id'])){ //新增数据
            $id = $this->add(); //添加基础内容
            if(!$id){
                $this->error = '新增基础内容出错！';
                return false;
            }
            $data['id'] = $id;
        } else { //更新数据
            $status = $this->save(); //更新基础内容
            if(false === $status){
                $this->error = '更新基础内容出错！';
                return false;
            }
        }

        /* 添加或新增扩展内容 */
        $logic = logic($data['model_id']);
        if(!$logic->autoSave($id)){
            if(isset($id)){ //新增失败，删除基础数据
                $this->delete($id);
            }
            $this->error = $logic->getError();
            return false;
        }

        //内容添加或更新完成
        return $data;
    }

    /**
     * 检查指定文档下面子文档的类型
     * @param intger $type 子文档类型
     * @param intger $pid 父文档类型
     * @return array 键值：status=>是否允许（0,1），'info'=>提示信息
     * @author 艺品网络  <twothink.cn>
     */
    public function checkDocumentType($type = null, $pid = null){
        $res = array('status'=>1, 'info'=>'');
        if(empty($type)){
            return array('status'=>0, 'info'=>'文档类型不能为空');
        }
        if(empty($pid)){
            return $res;
        }
        //查询父文档的类型
        $ptype = is_numeric($pid) ? $this->getFieldById($pid, 'type') : $this->getFieldByName($pid, 'type');
        //父文档为目录时
        switch($ptype){
            case 1: // 目录
            case 2: // 主题
                break;
            case 3: // 段落
                return array('status'=>0, 'info'=>'段落下面不允许再添加子内容');
            default:
                return array('status'=>0, 'info'=>'父文档类型不正确');
        }
        return $res;
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
