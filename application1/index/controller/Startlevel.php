<?php
// +----------------------------------------------------------------------
// | 功能：积分设置
// +----------------------------------------------------------------------
// | 作者: xiaomage
// +----------------------------------------------------------------------
// | 日期：2018-04-26
// +----------------------------------------------------------------------
namespace app\index\controller;
use app\index\controller\AdminBase;

class Startlevel extends AdminBase {

    /*
     * 星际等级列表
     */
    public function index(){
        $startLevel_model = new \app\index\model\StartLevel();
        if($this->request->isAjax()){
            $where = ['is_delete'=>0];
            return $startLevel_model->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 添加星际等级
     */
    public function add($id = 0) {
        $startLevel_model = new \app\index\model\StartLevel();
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $data = [
                'name'=>$request['name'],
                'value'=>$request['value'],
                'invite_people'=>$request['invite_people'],
                'contribution'=>$request['contribution'],
                'advanced_id'=>$request['advanced_id'],
                'bonus'=>$request['bonus'],
                'learn_accelerate'=>$request['learn_accelerate'],
                'small_sq'=>$request['small_sq']
            ];
          	$w['id'] = array('neq',$id);
          	$is_has = $startLevel_model->where(['advanced_id'=>$data['advanced_id']])->where($w)->find();
          	 
          	if($is_has){
            	$this->error('进阶已被选择');
            }
            //验证字段
            $id = $request['id'];
            $startLevel_validate = new \app\index\validate\StartLevel();
            if(!$startLevel_validate->check($data)) {
                $this->error($startLevel_validate->getError());
            }
            if (empty($id)) {
                if(!$startLevel_model->insert($data)){
                    $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $id];
                if (false === $startLevel_model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                $msg = '修改成功';
            }
            $this->success($msg);
        }
        // 赠送课程列表
        $advanced_model = new \app\index\model\Advanced();
        $advancedList = $advanced_model->select();
        $this->assign('advancedlislt',$advancedList);
        if($id) {
            $this->assign('id',$id);
            $info = $startLevel_model->where('id',$id)->find();
            $this->assign('info',$info);
        }
        return $this->fetch('add');
    }
     /*
     * 删除
     */
    public function del($id = 0) {
        $startLevel_model = new \app\index\model\StartLevel();
        $startLevel_model->where('id',$id)->delete()  && $this->success('删除成功');
        $this->error('删除失败');
    }

}
