<?php

namespace app\index\controller;
use app\index\controller\Base;
/**
 * 进阶等级
 * Class Advanced
 * @package app\index\controller
 */
class Advanced extends Base
{
    /*
     * 进阶列表
     */
    public function index() {
        $advanced_model = new \app\index\model\Advanced();
        if($this->request->isAjax()){
            $where = ['type'=>1,'is_delete'=>0];
            return $advanced_model->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 添加进阶
     */
    public function add(){
        if ($this->request->isPost()) {
            $advanced_model = new \app\index\model\Advanced();
            $request = $this->request->param();
            $data = [
                'name'=>$request['name'],
                'sort'=>$request['sort'],
                'type'=>1,
                'open_tj'=>$request['open_tj'],
                'difficulty'=>$request['difficulty'],
                'learn_power'=>$request['learn_power'],
                'reward'=>$request['reward'],
                'value'=>$request['value'],
                'chapter_count'=>$request['chapter_count'],
                'deadline'=>$request['deadline'],
                'studying_num'=>$request['studying_num'],
                'pay_type'=>$request['pay_type'] ? $request['pay_type'] : 1
            ];
            //验证字段
            $id = $request['id'];
            $advanced_validate = new \app\index\validate\Advanced();
            if(!$advanced_validate->scene('xcscourse')->check($data)) {
                $this->error($advanced_validate->getError());
            }
            if (empty($id)) {
                $data['addtime'] = time();
                if(!$advanced_model->insert($data)){
                    $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $id];
                if (false === $advanced_model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                $msg = '修改成功';
            }
            $this->success($msg);
        }
        return $this->fetch();
    }

    /*
     * 编辑
     */
    public function edit($id = 0) {
        $advanced_model = new \app\index\model\Advanced();
        $info = $advanced_model->where('id',$id)->find();
        $this->assign('info',$info);
        $info['str_pay_type'] = $info['pay_type'];
        $info['pay_type'] = explode(',',$info['pay_type']);
        $this->assign('id',$id);
        return $this->fetch('add');
    }
    
     /*
     * 删除
     */
    public function del($id = 0) {
        $model = new \app\index\model\Course();
        $is_has = $model->where(['advanced_id'=>$id,'is_delete'=>0])->count();
        if($is_has>0){
            $this->error('此进阶下还有课程，不能删除');return;
        }
        $xcsCourse_model = new \app\index\model\Advanced();
        $xcsCourse_model->where('id',$id)->update(['is_delete'=>1]) && $this->success('删除成功');
        $this->error('删除失败');
    }
}