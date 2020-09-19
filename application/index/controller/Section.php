<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\Sectiones;
use think\Db;

/**
 * 课程课时
 * Class Xcscourse
 * @package app\index\controller
 */
class Section extends Base
{
    /*
     * 课时列表
     * @param int $id
     * @param string $backType  1 学财商  2 才学堂
     * @return array|mixed
     */
    public function sectionList($id = 0) {
        $section_model = new Sectiones();
        $course_model = new \app\index\model\Course();
        $advanced_model = new \app\index\model\Advanced();
        if($this->request->isAjax()){
            $where = ['c_id'=>$id,'is_delete'=>0];
            return $section_model->getList($where);
        }
        $this->assign('id',$id);
        $courseInfo = $course_model->field('advanced_id,type')->where('id',$id)->find();
        $this->assign('advanced_id',$courseInfo['advanced_id']);
        $type = $advanced_model->where('id',$courseInfo['advanced_id'])->value('type');
        $this->assign('backType',$type);
        return $this->fetch();
    }

    /*
     * 新增课时
     * @param int $c_id  课程id
     */
    public function add($c_id = 0){
        if($this->request->isPost()) {
            $section_model = new Sectiones();
            $course_model = new \app\index\model\Course();
            $section_validate = new \app\index\validate\Sectiones();
            $advanced_model = new \app\index\model\Advanced();
            $courseInfo = $course_model->field('advanced_id,haschapter_num')->where('id',$c_id)->find();
            $chapter_count = $advanced_model->where('id',$courseInfo['advanced_id'])->value('chapter_count');
            $params = $this->request->param();
            $id = $params['id'];
            // 判断排序
            $sort = $params['sort'];
            $data = [
                'audiourl'=>$params['audiourl'],
                'name'=>$params['name'],
                'audiotime'=>$params['audiotime'],
                'audiosize'=>$params['audiosize'],
                'content'=>$params['content'],
                'c_id'=>$params['c_id'],
                'sort'=>$params['sort'],
                'addtime'=>time()
            ];
            if(!$section_validate->check($data)) {
                $this->error($section_validate->getError());
            }

            $url = '/index/Section/sectionList/id/'.$params['c_id'];
            if($id) {
                false !== $section_model->where('id',$id)->update($data) && $this->success('更新成功',$url);
            }
            if($chapter_count < $courseInfo['haschapter_num'] || $chapter_count == $courseInfo['haschapter_num']) {
                $this->error('课时数量已达上线');
            }
            Db::startTrans();
            $maxsort = $section_model->where(['c_id'=>$params['c_id']])->max('sort');
            if($sort > $maxsort) {
                if(!$section_model->insert($data)) {
                    $this->error('添加失败');
                }
            }else{
                $this->error('排序值不能重复');
            }

            if(false === $course_model->where('id',$c_id)->setInc('haschapter_num')) {
                Db::rollback();
                $this->error('添加失败');
            }
            Db::commit();
            $this->success('添加成功',$url);
        }
        $this->assign('c_id',$c_id);
        return $this->fetch();
    }

    /*
     * 编辑课时
     */
    public function edit($id = 0) {
        $section_model = new Sectiones();
        $course_model = new \app\index\model\Course();
        $info = $section_model->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('id',$id);
        $this->assign('c_id',$info['c_id']);
        $advanced_id = $course_model->where('id',$info['c_id'])->value('advanced_id');
        $this->assign('advanced_id',$advanced_id);
        return $this->fetch('add');
    }

    /*
     * 删除课时
     * @param id 课时id
     */
    public function del($id = 0) {
        $section_model = new Sectiones();
        $course_model = new \app\index\model\Course();
        $c_id = $section_model->where('id',$id)->value('c_id');
        Db::startTrans();
        if(false === $section_model->where('id',$id)->update(['is_delete'=>1])) {
            $this->error('删除失败');
        }
        if(false === $course_model->where('id',$c_id)->setDec('haschapter_num',1)) {
            Db::rollback();
            $this->error('删除失败');
        }
        Db::commit();
        $this->success('删除成功');

    }
}