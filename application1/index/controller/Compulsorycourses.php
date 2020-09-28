<?php

namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\Sectiones;
use app\index\model\Teacher;
use app\index\model\Course;
use think\Db;
class Compulsorycourses extends Base
{

    /*
     * 财商的觉醒 之课程
     */
    public function index() {
        $model = new \app\index\model\Course();
        if($this->request->isAjax()){
            $advanced_model = new \app\index\model\Advanced();
            $advanced_id = $advanced_model->where('type',3)->value('id');
            $where = ['advanced_id'=>$advanced_id,'is_delete'=>0];
            return $model->getList($where);
        }
        return $this->fetch();
    }

    /*
    * 课时列表
    */
    public function sectionList($id = 0) {
        $section_model = new Sectiones();
        if($this->request->isAjax()){
            $where = ['c_id'=>$id,'is_delete'=>0];
            return $section_model->getList($where);
        }
        $this->assign('id',$id);
        return $this->fetch();
    }

    /*
     * 财商的觉醒之信息
     */
    public function editcompulsory() {
        $model = new \app\index\model\CompulsoryCourses();
        if($this->request->isPost()){
            $validate = new \app\index\validate\CompulsoryCourses();
            $request = $this->request->param();
            //验证字段
            $id = $request['id'];
            unset($request['id']);
            $data = [
                'name'=>$request['name'],
                'abstract'=>$request['abstract'],
            ];
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            if (empty($id)) {
                $data['addtime'] = time();
                if(!$model->insert($data)){
                    $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $id];
                if (false === $model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                $msg = '修改成功';
            }
            $this->success($msg);
        }
        $info = $model->find();
        $this->assign('info',$info);
        return $this->fetch();
    }

    /*
     * 添加
     * 财商的觉醒之课程
     */
    public function add($id = 0) {
        $xcsCourse_model = new Course();
        if($this->request->isPost()) {
            $xcsCourse_validate = new \app\index\validate\XcsCourse();
            $advanced_model = new \app\index\model\Advanced();
            $advanced_id = $advanced_model->where('type',3)->value('id');
            $params = $this->request->param();
            $data = [
                'name'=>$params['name'],
                'abstract'=>$params['abstract'],
                'advanced_id'=>$advanced_id,
                'imgurl'=>$params['imgurl'],
                'teacher_id'=>$params['teacher_id'],
                'type'=>4,
                'course_bright'=>$params['course_bright'],
                'complete_course'=>$params['complete_course'],
                'addtime'=>time()
            ];
            if(!$xcsCourse_validate->scene('compulsorycourse')->check($data)) {
                $this->error($xcsCourse_validate->getError());
            }
            $id = $params['id'];
            if($id) {
                if(false !== $xcsCourse_model->where('id',$id)->update($data)) {
                    $this->success('修改成功');
                }
                $this->error('修改失败');
            }
            if($xcsCourse_model->insert($data)) {
                $this->success('添加成功');
            }
            $this->error('添加失败');
        }
        if($id) {
            $info = $xcsCourse_model->where('id',$id)->find();
            $this->assign('info',$info);
            $this->assign('id',$id);
        }
        // 老师列表
        $teacher_model = new Teacher();
        $teacher = $teacher_model->select();
        $this->assign('teacher',$teacher);
        return $this->fetch();
    }

    /*
     * 删除
     */
    public function del($id = 0) {
        if($this->request->isAjax()) {
            // 查看是否已经添加了课时
            $section_model = new Sectiones();
            if($section_model->where('c_id',$id)->find()){
                $this->error('请先删除该课程下的课时');
            }
            $xcsCourse_model = new Course();
            false !== $xcsCourse_model->where('id',$id)->update(['is_delete'=>1]) && $this->success('删除成功');
            $this->error('删除失败');
        }
    }

}