<?php


namespace app\index\controller;
use app\index\controller\Base;
use think\Db;
/**
 * 教师管理
 * Class Xcscourse
 * @package app\index\controller
 */
class Teacher extends Base
{
    public function index() {
        $teacher_model = new \app\index\model\Teacher();
        if($this->request->isAjax()){
            $where = [];
            return $teacher_model->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 编辑老师
     */
    public function addteacher(){
        if ($this->request->isPost()) {
            $teacher_model = new \app\index\model\Teacher();
            $teacher_validate = new \app\index\validate\Teacher();
            $user_model = new \app\index\model\User();
            $request = $this->request->param();
            //验证字段
            $id = $request['id'];
            unset($request['id']);
            $useInfo = $user_model->where('tel',$request['tel'])->find();
            if(empty($userInfo)) {
               // $this->success('不存在该用户');
            }
            $uid = $useInfo['id'];
            $data = [
                'name'=>$request['name'],
                'introduction'=>$request['introduction'],
                'headimg'=>$request['headimg'],
                'imgurl'=>$request['imgurl'],
                'tel'=>$request['tel'],
                'uid'=>$uid
            ];
            if (!$teacher_validate->check($data)) {
                $this->error($teacher_validate->getError());
            }
            Db::startTrans();
            if (empty($id)) {
                $data['addtime'] = time();
                if(!$teacher_model->insert($data)){
                   $this->error('添加失败');
                }
                if(false === $user_model->where('id',$uid)->update(['is_teacher'=>1])){
                    Db::rollback();
                }
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $id];
                $teacherInfo = $teacher_model->where($where)->find();
                if (false === $teacher_model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                if($teacherInfo['uid'] != $uid) {
                    if(false === $user_model->where('id',$teacherInfo['uid'])->update(['is_teacher'=>0])){
                        Db::rollback();
                    }
                }
                if(false === $user_model->where('id',$uid)->update(['is_teacher'=>1])){
                    Db::rollback();
                }
                $msg = '修改成功';
            }
            Db::commit();
            $this->success($msg);
        }
        return $this->fetch('add');
    }

    /**编辑老师信息
     * @param int $id
     */
    public function edit($id = 0) {
        $teacher_model = new \app\index\model\Teacher();
        $teacherInfo = $teacher_model->where('id',$id)->find();
        $this->assign('info',$teacherInfo);
        $this->assign('id',$id);
        return $this->fetch('add');
    }
}