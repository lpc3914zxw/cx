<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\Teacher;
/**
 * 才学堂-课程列表
 * Class course
 * @package app\index\controller
 */
class Course extends Base
{
    /*
    * 分类
    */
    public function category() {
        $advanced_model = new \app\index\model\Advanced();
        if($this->request->isAjax()){
            $where = ['type'=>2,'is_delete'=>0];
            return $advanced_model->getList($where);
        }
        return $this->fetch();
    }
     /*
     * 删除分类
     */
    public function delcategory($id = 0) {
        $model = new \app\index\model\Course();
        $is_has = $model->where(['advanced_id'=>$id,'is_delete'=>0])->count();
        if($is_has>0){
            $this->error('此分类下还有课程');return;
        }
        
        $xcsCourse_model = new \app\index\model\Advanced();
        $xcsCourse_model->where('id',$id)->update(['is_delete'=>1]) && $this->success('删除成功');
        $this->error('删除失败');
    }
    /*
     *添加分类
     */
    public function addcategory($id = 0) {
        $advanced_model = new \app\index\model\Advanced();
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $data = [
                'name'=>$request['name'],
                'sort'=>$request['sort'],
                'imgurl'=>$request['imgurl'],
                'value'=>$request['value'],
                'chapter_count'=>$request['chapter_count'],
                'pay_type'=>$request['pay_type'],
                'addtime'=>time(),
                'type'=>2
            ];
            //验证字段
            $editId = $request['id'];
            $validate = new \app\index\validate\Category();
            if(!$validate->check($data)) {
                $this->error($validate->getError());
            }
            if (empty($editId)) {
                if(!$advanced_model->insert($data)){
                    $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $editId];
                if (false === $advanced_model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                $msg = '修改成功';
            }
            $this->success($msg);
        }
        if($id) {
            $advanced_model = new \app\index\model\Advanced();
            $info = $advanced_model->where('id',$id)->find();
            $this->assign('info',$info);
            $info['str_pay_type'] = $info['pay_type'];
            $info['pay_type'] = explode(',',$info['pay_type']);
            $this->assign('id',$id);
        }
        return $this->fetch();
    }

    /*
     * 分类课程
     */
    public function course($cate_id = 0) {
        $model = new \app\index\model\Course();
        if($this->request->isAjax()){
            // 搜索条件
            $where = ['is_delete'=>0];
            $params = $this->request->param();
            (isset($params['name']) && !empty($params['name'])) && $where['name'] = ['like', '%' . $params['name'] . '%'];
            $where['advanced_id'] = $cate_id;
            return $model->getList($where);
        }
        $this->assign('cate_id',$cate_id);
        return $this->fetch();
    }


    /*
     * 课程列表
     */
    public function index() {
        $model = new \app\index\model\Course();
        $advanced_model = new \app\index\model\Advanced();
        $advancedList = $advanced_model->where('type',2)->select();
        if($this->request->isAjax()){
            //搜索条件
            $where = ['is_delete'=>0];
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
            }
            return $model->getList($where);
        }
        $this->assign('advancedlist',$advancedList);
        return $this->fetch();
    }

    /*
     * 设置是否每日一课程/ 推荐
     */
    public function setTrue($id = 0,$type = 0,$value = '') {
        $curse_model = new \app\index\model\Course();
        if($type == 1) {
            false !== $curse_model->where('id',$id)->update(['is_tj'=>$value]) && $this->success('设置成功');
            $this->error('设置失败');
        }else if($type == 2){
            if($value == 0) {
                false !== $curse_model->where('id',$id)->update(['is_daycourse'=>$value]) && $this->success('设置成功');
                $this->error('设置失败');
            }else{
                false === $curse_model->where(['id'=>['neq',$id]])->update(['is_daycourse'=>0]) && $this->error('设置失败');
                false !== $curse_model->where('id',$id)->update(['is_daycourse'=>1]) && $this->success('设置成功');
                $this->error('设置失败');
            }
        }else{
            false !== $curse_model->where('id',$id)->update(['is_shelves'=>$value]) && $this->success('设置成功');
            $this->error('设置失败');
        }
    }


    /*
      * 添加
      * 财务自由之路课程
      */
    public function add($id = 0) {
        $curse_model = new \app\index\model\Course();
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
                'type'=>2,
                'course_bright'=>$params['course_bright'],
                'addtime'=>time(),
                'is_shelves'=>$params['is_shelves']
            ];
            if(!$xcsCourse_validate->check($data)) {
                $this->error($xcsCourse_validate->getError());
            }
            $editId = $params['id'];
            if($editId) {
                if(false !== $curse_model->where('id',$editId)->update($data)) {
                    $this->success('修改成功');
                }
                $this->error('修改失败');
            }
            if($curse_model->insert($data)) {
                $this->success('添加成功');
            }
            $this->error('添加失败');
        }
        if($id) {
            $info = $curse_model->where('id',$id)->find();
            $this->assign('info',$info);
            $this->assign('advanced_id',$info['advanced_id']);
            $this->assign('id',$id);
        }
        // 老师列表
        $teacher_model = new Teacher();
        $teacher = $teacher_model->select();
        $this->assign('teacher',$teacher);
        // 分类列表
        $advanced_model = new \app\index\model\Advanced();
        $advancedList = $advanced_model->where('type',2)->select();
        $this->assign('advancedlist',$advancedList);
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
//    public function editCourse($id = 0) {
//        $xcsCourse_model = new \app\index\model\Course();
//        $info = $xcsCourse_model->where('id',$id)->find();
//        $this->assign('info',$info);
//        $this->assign('id',$id);
//        $this->assign('advanced_id',$info['advanced_id']);
//        $teacher_model = new Teacher();
//        $teacher = $teacher_model->select();
//        $this->assign('teacher',$teacher);
//        return $this->fetch('addcourse');
//    }

    /*
     * 删除课程
     */
    public function del($id = 0) {
        $xcsCourse_model = new \app\index\model\Course();
        $xcsCourse_model->where('id',$id)->update(['is_delete'=>1]) && $this->success('删除成功');
        $this->error('删除失败');
    }
}