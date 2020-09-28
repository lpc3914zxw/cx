<?php
namespace app\index\controller;

use app\index\controller\AdminBase;
use app\index\model\IntegralDetails;
use app\index\model\Member;
use app\index\model\Message;
use app\index\model\Browsrecord;
use app\index\model\AuthGroup;
use app\index\model\AuthGroupAccess;

use think\Db;
use think\Loader;
use think\Config;
use think\Url;
use think\Request;
use think\Session;

/**
 * Class Index
 * @author Steed
 * @package app\index\controller
 */
class Feedbackcategory extends AdminBase {
    /*分类列表*/
    public function cate_list(){
        
        $auth = new \think\Auth();
        $request = Request::instance();
        $m = $request->module();
        $c = $request->controller();
        $a = $request->action();
        $rule_name = $m.'/'.$c.'/'.$a;

        $result = $auth->check($rule_name,$this->partner['uid']);
        if(!$result){
            $this->error('您没有权限访问');
        }
        
        //$data = $FeedbackCategory_model->getTreeData('tree','id','name');
        $data = Db::name('FeedbackCategory')->where('pid',0)->where('status',1)->order('sort','desc')->select();
        foreach($data as $dkey=>$dval){
            $data[$dkey]['child'] = Db::name('FeedbackCategory')->where('pid',$dval['id'])->where('status',1)->order('sort','desc')->select();
        }
        $assign = array(
            'data'=>$data
        );
        
        $this->assign($assign);
        return $this->fetch();
    }
    /**
     * 添加分类
     */
    public function add(){
        $data = input('post.');
        unset($data['id']);
        
        $result = Db::name('FeedbackCategory')->insert($data);
        if ($result) {
            $this->success('添加成功','/index/feedbackcategory/cate_list');
        }else{
            $this->error('添加失败');
        }
    }

    /**
     * 修改分类
     */
    public function edit(){
        $data=input('post.');
        
        $info = ['name'=>$data['name'],'sort'=>$data['sort']];
        $result = Db::name('FeedbackCategory')->where(["id"=>$data['id']])->update($info);
        if ($result) {
            $this->success('修改成功','/index/feedbackcategory/cate_list');
        }else{
            $this->error('您没有做任何修改');
        }
    }

    /**
     * 删除分类
     */
    public function delete($id){
        
        if(Db::name('FeedbackCategory')->where('pid',$id)->find()){
            $this->error('请先删除子权限');
        }
        $result = Db::name('FeedbackCategory')->where('id',$id)->delete();
        if($result){
            $this->success('删除成功','/index/feedbackcategory/cate_list');
        }else{
            $this->error('删除失败','/index/feedbackcategory/cate_list');
        }
    }

    
}
