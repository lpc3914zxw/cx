<?php

namespace app\wxapp\model;

use app\index\model\Advanced;
use think\Model;

class Orders extends Model {
    protected $table = 'order';
    /*
     * 获取已购买的课程
     */
    public function getLearnCourse($where = [],$limit = '') {
        $field = ['c.teacher_id,c.id,c.advanced_id,c.name,c.abstract,c.chapter_count,o.paytime,o.status'];
        $join = [
           ['course c','c.id=o.course_id','left']
        ];
        $data = $this::alias('o')->field($field)->join($join)->where($where)->limit($limit)->select();
        $teacher_model = new Teachers();
        $advanced_model = new Advanced();
        foreach ($data as $k=>$val) {
            $data[$k]['paytime'] = date('Y.m.d',$val['paytime']);
            $data[$k]['teacherImg'] = $teacher_model->where('id',$val['teacher_id'])->value('headimg');
            if($val['advanced_id'] == 0) {
                $data[$k]['name'] = "财商基础知识包:《".$val['name']."》";
            }else{
                $advancedName = $advanced_model->where('id',$val['advanced_id'])->value('name');
                $data[$k]['name'] = $advancedName.":《".$val['name']."》";
            }
            if($val['status'] == 1) {
                $data[$k]['status'] = '学习中';
            }else {
                $data[$k]['status'] = '已学完';
            }
        }
        return returnjson('1000',$data,'获取成功');
    }
}