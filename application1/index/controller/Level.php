<?php

namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\Levels;
/**
 * 会员等级
 * Class Advanced
 * @package app\index\controller
 */
class Level extends Base
{
    /*
     * 会员等级列表
     */
    public function index() {
        $level_model = new Levels();
        if($this->request->isAjax()){
            $where = ['is_delete'=>0];
            return $level_model->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 添加会员等级
     */
    public function addlevel(){
        if ($this->request->isPost()) {
            $level_model = new Levels();
            $request = $this->request->param();
            $data = [
                'name'=>$request['name'],
                'service_charge'=>$request['service_charge'],
                'value'=>$request['value'],
                'type'=>$request['type']
            ];
            //验证字段
            $id = $request['id'];
            $level_validate = new \app\index\validate\Levels();
            if(!$level_validate->check($data)) {
                $this->error($level_validate->getError());
            }
            if($request['type'] == 2) {
                if(empty($request['invite_people']) || $request['invite_people'] < 1) {
                    $this->error('请输入邀请人数');
                }
                $data['invite_people'] = $request['invite_people'];
            }
            if (empty($id)) {
                if(!$level_model->insert($data)){
                    $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $id];
                if (false === $level_model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                $msg = '修改成功';
            }
            $this->success($msg);
        }
        return $this->fetch('add');
    }

    /*
     * 编辑
     */
    public function edit($id = 0) {
        $level_model = new Levels();
        $info = $level_model->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('id',$id);
        return $this->fetch('add');
    }
     /*
     * 删除
     */
    public function del($id = 0) {
        $xcsCourse_model = new Levels();
        $xcsCourse_model->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }
}