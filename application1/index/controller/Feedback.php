<?php

namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\Levels;
use think\Db;
/**
 * 反馈列表
 * Class Advanced
 * @package app\index\controller
 */
class Feedback extends Base
{
    /*
     * 反馈列表
     */
    public function index() {
        $feedback = new \app\index\model\Feedback();
        $feedbackcate = Db::name('feedback_category')->where('pid','neq',1)->where('status',1)->order('sort','desc')->group('pid')->select();
        foreach($feedbackcate as $fkey=>$fval){
            $feedbackcate[$fkey]['_name'] = Db::name('feedback_category')->where('id',$fval['id'])->value('name').'-'.$fval['name'];
        }
        $this->assign('cate',$feedbackcate);
        $params = input('param.');
        if($this->request->isAjax()){
            $where = [];
            if(!empty($params['status'])){
                $where['f.status'] = $params['status'];
            }
            if(!empty($params['categroy'])){
                $where['categroy'] = $params['categroy'];
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['addtime'] = array('between',$time1.','.$time2);
            }
            return $feedback->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 单条删除
     */
    public function del($id = 0) {
        $this->request->isAjax() || $this->error('非法请求');
        $feedback = new \app\index\model\Feedback();
        $feedback->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }
    /*
     * 回复
     */
    public function reply($id = 0,$reply) {
        
        $feedback = new \app\index\model\Feedback();
        if(empty($reply)||empty($id)){
            $this->error('回复内容不能为空');
        }
        $feedback->where('id',$id)->update(['reply'=>$reply,'status'=>1]) && $this->success('已回复');
        $this->error('回复失败');
    }
/*
     * 回复页面
     */
    public function edit($id = 0) {
        
        $feedback = new \app\index\model\Feedback();
        if(empty($id)){
            $this->error('参数为空');
        }
        $info = $feedback->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('id',$id);
        return $this->fetch();
    }
    /*
     * 批量删除
     * @param string $ids
     */
    public function piDel($ids = []) {
        $this->request->isAjax() || $this->error('非法请求');
        $feedback = new \app\index\model\Feedback();
        $feedback->where(['id'=>['in',$ids]])->delete() && $this->success('已删除');
        $this->error('删除失败');
    }
}