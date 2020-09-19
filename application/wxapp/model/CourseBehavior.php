<?php

namespace app\wxapp\model;

use app\index\model\Advanced;
use app\index\model\Course;
use app\index\model\Sectiones;
use think\Model;

/*
 * 课程行为表  点赞  收藏
 */
class CourseBehavior extends Model {
    protected $table = 'course_behavior';

    /*
    * 收藏列表
    * @param $where
    * @param $limit
    */
    public function getList($where,$limit) {
        $data = $this::field('id,s_id,addtime')->where($where)->limit($limit)->select();
        $section_model = new Sectiones();
        $course_model = new Course();
        $teacher_model = new Teachers();
        $advanced_model = new Advanced();
        if(empty($data)) {
            return returnjson(1000,[],'获取成功');
        }
        foreach ($data as $k=>$val) {
            $section = $section_model->field('name,c_id,addtime')->where('id',$val['s_id'])->find();
            $courseInfo = $course_model->field('teacher_id,advanced_id,name')->where('id',$section['c_id'])->find();
            $advancedName = $advanced_model->where('id',$courseInfo['advanced_id'])->value('name');
            $data[$k]['name'] = $section['name'];
            $data[$k]['txt'] = $advancedName.":".$courseInfo['name'];
            $data[$k]['addtime'] = tranTime($val['addtime']);
            $teacherImg = $teacher_model->where('id',$courseInfo['teacher_id'])->value('headimg');
            $data[$k]['teacherImg'] = $teacherImg;
        }
        return returnjson(1000,$data,'获取成功');
    }
}