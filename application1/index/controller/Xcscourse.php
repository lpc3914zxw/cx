<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\Teacher;
/**
 * 学财商-课程列表
 * Class Xcscourse
 * @package app\index\controller
 */
class Xcscourse extends Base
{
    /*
     * 课程列表
     */
    public function index() {
        $model = new \app\index\model\Course();
        $advanced_model = new \app\index\model\Advanced();
      $where['type'] = array('in','1,3');
        $advancedList = $advanced_model->where(['is_delete'=>0])->where($where)->select();
        if($this->request->isAjax()){
            $where = ['is_delete'=>0];
            //搜索条件
            $params = $this->request->param();
            (isset($params['name']) && !empty($params['name'])) && $where['name'] = ['like', '%' . $params['name'] . '%'];
            if(isset($params['advanced_id']) && !empty($params['advanced_id'])){
                $where['advanced_id'] = $params['advanced_id'];
            }else{
              	$ids = [];
                if($advancedList) {
                    foreach ($advancedList as $val) {
                        $ids[] = $val['id'];
                    }
                    $ids = implode(',',$ids);
                }
                $where['advanced_id'] = ['in',($ids)];
                //$where['advanced_id'] = ['neq',0];
            }
            return $model->getList($where);
        }
       
        $this->assign('advancedlist',$advancedList);
        return $this->fetch();
    }

    /*
      * 添加
      * 财务自由之路课程
      */
    public function add($id = 0) {
        $xcsCourse_model = new \app\index\model\Course();
        if($this->request->isPost()) {
            $xcsCourse_validate = new \app\index\validate\XcsCourse();
            $params = $this->request->param();
            $data = [
                'name'=>$params['name'],
                'abstract'=>$params['abstract'],
                'imgurl'=>$params['imgurl'],
                'teacher_id'=>$params['teacher_id'],
                'samll_imgurl'=>$params['samll_imgurl'],
                'complete_course'=>$params['complete_course'],
                'advanced_id'=>$params['advanced_id'],
                'stock'=>$params['stock'],
                'type'=>2,
                'course_bright'=>$params['course_bright'],
                'addtime'=>time(),
                'is_shelves'=>$params['is_shelves']
            ];
            if(!$xcsCourse_validate->check($data)) {
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
        // 进阶列表
        $advanced_model = new \app\index\model\Advanced();
        $advancedList = $advanced_model->where('type','in',[1,3])->select();
        $this->assign('advancedlist',$advancedList);
        return $this->fetch();
    }

    /*
     * 对应进阶课程
     */
    public function advanced_index($advanced_id = 0) {
        $model = new \app\index\model\Course();
        if($this->request->isAjax()){
            $where = ['is_delete'=>0];
            //搜索条件
            $params = $this->request->param();
            (isset($params['name']) && !empty($params['name'])) && $where['name'] = ['like', '%' . $params['name'] . '%'];
            $where['advanced_id'] = $advanced_id;
            return $model->getList($where);
        }
        $this->assign('advanced_id',$advanced_id);
        return $this->fetch();
    }

    /*
     * 由进阶列表里添加课程
     * @param int $advanced_id 进阶id
     * @return mixed
     */
    public function addCourse($advanced_id = 0) {
        $this->assign('advanced_id',$advanced_id);
        // 老师列表
        $teacher_model = new Teacher();
        $teacher = $teacher_model->select();
        $this->assign('teacher',$teacher);
        return $this->fetch('addcourse');
    }

    /*
     * 由进阶列表里 修改课程
     */
    public function editCourse($id = 0) {
        $xcsCourse_model = new \app\index\model\Course();
        $info = $xcsCourse_model->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('id',$id);
        $this->assign('advanced_id',$info['advanced_id']);
        $teacher_model = new Teacher();
        $teacher = $teacher_model->select();
        $this->assign('teacher',$teacher);
        return $this->fetch('addcourse');
    }

    /*
     * 删除课程
     */
    public function del($id = 0) {
        $xcsCourse_model = new \app\index\model\Course();
        $xcsCourse_model->where('id',$id)->update(['is_delete'=>1]) && $this->success('删除成功');
        $this->error('删除失败');
    }

    /*
     * 财商的觉醒设置
     */
    public function set() {
        $advanced_model = new \app\index\model\Advanced();
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $data = [
                'name'=>'财商的觉醒',
                'type'=>3,
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
        $info = $advanced_model->where('type',3)->find();
        $info['str_pay_type'] = $info['pay_type'];
        $info['pay_type'] = explode(',',$info['pay_type']);
        $this->assign('id',$info['id']);
        $this->assign('info',$info);
        return $this->fetch();
    }

    /*
     * 学财商之财务自由之路之设置
     */
    public function xcsSet() {
        $system_model = new \app\index\model\System();
        if($this->request->isPost()) {
            $xcsimg = input('xcsimg');
            $id = input('id');
            $system_model->where('id',$id)->update(['xcsimg'=>$xcsimg]) && $this->success('修改成功');
            $this->error('修改失败');
        }
        $systemInfo = $system_model->find();
        $this->assign('info',$systemInfo);
        return $this->fetch('xcsimg');
    }
}