<?php

namespace app\wxapp\model;

use app\index\model\Advanced;
use app\index\model\Course;
use app\index\model\Sectiones;
use think\Model;
/*
 * 收藏
 */
class Collection extends Model {
    protected $table = 'collection';

    /*
     * 收藏列表
     * @param $where
     * @param $limit
     */
    public function getList($where,$limit) {
        $data = $this::where($where)->limit($limit)->select();
        $section_model = new Sectiones();
        $course_model = new Course();
        $advanced = new Advanced();
        $teacher_model = new Teachers();
        foreach ($data as $k=>$val) {
            if($val['type'] == 1) {

            }else{
                $section = $section_model->field('name,c_id,addtime')->where('id',$val['obj_id'])->find();
                $courseInfo = $course_model->field('teacher_id,advanced_id')->where('id',$section['c_id'])->find();
                $data[$k]['name'] = $section['name'];
                $data[$k]['addtime'] = tranTime($val['addtime']);
                $teacherImg = $teacher_model->where('id',$courseInfo['teacher_id'])->value('headimg');
                $data[$k]['teacherImg'] = $teacherImg;
            }
        }
        return $data;
    }
}